<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\ClientQuestionnaire;
use App\Models\Invoice;
use App\Notifications\InvoiceIssued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoicingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->syncPermissions();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function invoicePayload(Client $client, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'number' => 'INV-2026-0001',
            'subject' => 'Clinic website',
            'issue_date' => today()->format('Y-m-d'),
            'due_date' => today()->addDays(14)->format('Y-m-d'),
            'currency' => 'ghs',
            'items' => [
                ['description' => 'Design', 'quantity' => '2', 'unit_price' => '1,500.00'],
                ['description' => 'Hosting setup', 'quantity' => '1.5', 'unit_price' => '200'],
                ['description' => '', 'quantity' => '1', 'unit_price' => ''],
            ],
            'discount' => '100',
            'tax_label' => 'VAT',
            'tax_rate' => '15',
            'payment_instructions' => 'MoMo 024 000 0000',
        ], $overrides);
    }

    public function test_staff_add_a_client_and_create_an_invoice_with_server_calculated_totals(): void
    {
        $this->actingAsRole('Administrator');

        $this->post(route('admin.clients.store'), ['name' => 'Ama Mensah', 'company' => 'Mensah Clinics', 'email' => 'ama@example.com', 'currency' => 'ghs'])
            ->assertRedirect();
        $client = Client::query()->sole();
        $this->assertSame('GHS', $client->currency);

        $this->post(route('admin.invoices.store'), $this->invoicePayload($client))->assertRedirect();

        $invoice = Invoice::query()->with('items')->sole();
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame('GHS', $invoice->currency);
        $this->assertCount(2, $invoice->items);
        // 2 × 1,500 + 1.5 × 200 = 3,300; less 100 discount = 3,200; 15% tax = 480; total 3,680.
        $this->assertSame(330000, $invoice->subtotal);
        $this->assertSame(10000, $invoice->discount);
        $this->assertSame(48000, $invoice->tax_amount);
        $this->assertSame(368000, $invoice->total);
        $this->assertSame('Mensah Clinics', $invoice->bill_to['company']);
        $this->assertSame(48, strlen($invoice->token));
        $this->assertDatabaseMissing('invoices', ['token' => $invoice->token]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'invoice.created']);

        $this->get(route('admin.invoices.show', $invoice))->assertOk()->assertSee('INV-2026-0001')->assertSee('GHS 3,680.00');
        $this->get(route('admin.clients.show', $client))->assertOk()->assertSee('INV-2026-0001');
        $this->get(route('admin.invoices.index'))->assertOk()->assertSee('Mensah Clinics');
    }

    public function test_invoice_numbers_follow_the_prefix_and_year(): void
    {
        $this->actingAsRole('Administrator');
        $this->setting(['invoicing.number_prefix' => 'DX']);
        Invoice::factory()->create(['number' => 'DX-'.now()->year.'-0007']);

        $this->get(route('admin.invoices.create'))
            ->assertOk()
            ->assertSee('value="DX-'.now()->year.'-0008"', false);
    }

    public function test_invoices_need_at_least_one_line_and_a_unique_number(): void
    {
        $this->actingAsRole('Administrator');
        $client = Client::factory()->create();
        Invoice::factory()->create(['number' => 'INV-2026-0001']);

        $this->post(route('admin.invoices.store'), $this->invoicePayload($client, ['items' => [['description' => '', 'quantity' => '', 'unit_price' => '']]]))
            ->assertSessionHasErrors(['items', 'number']);

        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_client_link_works_only_once_the_invoice_is_sent(): void
    {
        $invoice = Invoice::factory()->create();

        $this->get(route('invoices.show', $invoice->token))->assertNotFound();

        $invoice->forceFill(['status' => InvoiceStatus::Sent, 'sent_at' => now()])->save();

        $this->get(route('invoices.show', $invoice->token))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee($invoice->number)
            ->assertSee('Website design');

        $this->assertNotNull($invoice->fresh()->viewed_at);
        $this->get('/invoices/'.str_repeat('a', 48))->assertNotFound();
    }

    public function test_emailing_an_invoice_marks_it_sent_and_notifies_the_client(): void
    {
        Notification::fake();
        $this->actingAsRole('Administrator');
        $invoice = Invoice::factory()->create();

        $this->post(route('admin.invoices.email', $invoice), ['email' => 'billing@example.com'])->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
        $this->assertNotNull($invoice->sent_at);
        Notification::assertSentTo(new AnonymousNotifiable, InvoiceIssued::class, function (InvoiceIssued $notification, array $channels, AnonymousNotifiable $notifiable) use ($invoice) {
            $mail = $notification->toMail($notifiable);

            return $notifiable->routes['mail'] === 'billing@example.com'
                && str_contains($mail->subject, $invoice->number)
                && $mail->actionUrl === $invoice->shareUrl();
        });
    }

    public function test_payments_move_an_invoice_to_part_paid_then_paid(): void
    {
        $this->actingAsRole('Administrator');
        $invoice = Invoice::factory()->sent()->create();

        $this->post(route('admin.invoices.payments.store', $invoice), ['amount' => '400', 'paid_on' => today()->format('Y-m-d'), 'method' => 'mobile_money', 'reference' => 'TX1'])
            ->assertRedirect();
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->status);
        $this->assertSame(60000, $invoice->balance());

        $this->post(route('admin.invoices.payments.store', $invoice), ['amount' => '600.00', 'paid_on' => today()->format('Y-m-d'), 'method' => 'bank_transfer']);
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame(0, $invoice->balance());

        $payment = $invoice->payments()->latest('id')->first();
        $this->delete(route('admin.invoices.payments.destroy', [$invoice, $payment]))->assertRedirect();
        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->paid_at);
    }

    public function test_payment_validation_uses_its_own_error_bag(): void
    {
        $this->actingAsRole('Administrator');
        $invoice = Invoice::factory()->sent()->create();

        $this->post(route('admin.invoices.payments.store', $invoice), ['amount' => '0', 'paid_on' => today()->addDay()->format('Y-m-d'), 'method' => 'barter'])
            ->assertSessionHasErrorsIn('payment', ['amount', 'paid_on', 'method']);
    }

    public function test_sent_invoices_are_voided_not_deleted(): void
    {
        $this->actingAsRole('Administrator');
        $sent = Invoice::factory()->sent()->create();
        $draft = Invoice::factory()->create();

        $this->delete(route('admin.invoices.destroy', $sent))->assertSessionHas('error');
        $this->assertModelExists($sent);

        $this->delete(route('admin.invoices.destroy', $draft))->assertRedirect();
        $this->assertModelMissing($draft);

        $this->post(route('admin.invoices.void', $sent))->assertRedirect();
        $this->assertSame(InvoiceStatus::Void, $sent->fresh()->status);
        $this->get(route('admin.invoices.edit', $sent))->assertRedirect(route('admin.invoices.show', $sent));
        $this->get(route('invoices.show', $sent->token))->assertOk()->assertSee('VOID');

        $this->post(route('admin.invoices.restore', $sent))->assertRedirect();
        $this->assertSame(InvoiceStatus::Sent, $sent->fresh()->status);
    }

    public function test_clients_with_invoices_cannot_be_deleted(): void
    {
        $this->actingAsRole('Administrator');
        $invoice = Invoice::factory()->create();
        $empty = Client::factory()->create();

        $this->delete(route('admin.clients.destroy', $invoice->client))->assertSessionHas('error');
        $this->assertModelExists($invoice->client);

        $this->delete(route('admin.clients.destroy', $empty))->assertRedirect(route('admin.clients.index'));
        $this->assertModelMissing($empty);
    }

    public function test_editing_a_client_does_not_change_sent_invoices(): void
    {
        $this->actingAsRole('Administrator');
        $client = Client::factory()->create(['company' => 'Old Name Ltd']);
        $this->post(route('admin.invoices.store'), $this->invoicePayload($client));
        $invoice = Invoice::query()->sole();
        $this->post(route('admin.invoices.send', $invoice));

        $this->put(route('admin.clients.update', $client), ['name' => $client->name, 'company' => 'New Name Ltd']);

        $this->get(route('invoices.show', $invoice->token))->assertSee('Old Name Ltd')->assertDontSee('New Name Ltd');
    }

    public function test_duplicating_creates_a_new_draft_with_the_same_lines(): void
    {
        $this->actingAsRole('Administrator');
        $client = Client::factory()->create();
        $this->post(route('admin.invoices.store'), $this->invoicePayload($client));
        $original = Invoice::query()->sole();

        $this->post(route('admin.invoices.duplicate', $original))->assertRedirect();

        $copy = Invoice::query()->whereKeyNot($original->id)->sole();
        $this->assertSame(InvoiceStatus::Draft, $copy->status);
        $this->assertNotSame($original->number, $copy->number);
        $this->assertSame($original->total, $copy->total);
        $this->assertSame(2, $copy->items()->count());
    }

    public function test_overdue_invoices_are_flagged(): void
    {
        $this->actingAsRole('Administrator');
        $overdue = Invoice::factory()->overdue()->create();
        Invoice::factory()->sent()->create();

        $this->assertTrue($overdue->isOverdue());
        $this->assertSame(1, Invoice::query()->overdue()->count());
        $this->get(route('admin.invoices.index', ['status' => 'overdue']))->assertOk()->assertSee($overdue->number);
    }

    public function test_client_form_can_be_prefilled_from_an_onboarding_questionnaire(): void
    {
        $this->actingAsRole('Administrator');
        $questionnaire = ClientQuestionnaire::factory()->submitted()->create();

        $this->get(route('admin.clients.create', ['questionnaire' => $questionnaire->id]))
            ->assertOk()
            ->assertSee('value="Ama Mensah"', false)
            ->assertSee('value="Mensah Clinics"', false);
    }

    public function test_roles_without_invoice_permissions_are_refused(): void
    {
        $invoice = Invoice::factory()->create();
        $this->actingAsRole('Editor');

        $this->get(route('admin.invoices.index'))->assertForbidden();
        $this->get(route('admin.clients.index'))->assertForbidden();
        $this->get(route('admin.invoices.show', $invoice))->assertForbidden();
        $this->post(route('admin.clients.store'), ['name' => 'X'])->assertForbidden();
        $this->get(route('admin.settings.edit', 'invoicing'))->assertForbidden();
    }

    public function test_invoice_settings_are_saved(): void
    {
        $this->actingAsRole('Administrator');

        $this->get(route('admin.settings.edit', 'invoicing'))->assertOk()->assertSee('Payment instructions');

        $this->put(route('admin.settings.update', 'invoicing'), ['settings' => [
            'invoicing-currency' => 'usd',
            'invoicing-number_prefix' => 'DX',
            'invoicing-payment_days' => '30',
            'invoicing-tax_rate' => '12.5',
            'invoicing-payment_instructions' => 'Bank: Example Bank',
        ]])->assertRedirect();

        $this->get(route('admin.invoices.create'))->assertRedirect(route('admin.clients.create'));
        Client::factory()->create();
        $this->get(route('admin.invoices.create'))
            ->assertOk()
            ->assertSee('DX-'.now()->year.'-0001')
            ->assertSee('Bank: Example Bank')
            ->assertSee('value="'.today()->addDays(30)->format('Y-m-d').'"', false);
    }
}
