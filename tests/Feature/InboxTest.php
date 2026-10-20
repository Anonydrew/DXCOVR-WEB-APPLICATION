<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Enums\SubscriberStatus;
use App\Models\Enquiry;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Notifications\ConfirmNewsletterSubscription;
use App\Notifications\EnquiryAcknowledgement;
use App\Notifications\NewEnquiryReceived;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->syncPermissions();
    }

    /**
     * @return array<string, mixed>
     */
    protected function enquiry(array $overrides = []): array
    {
        return array_merge([
            'type' => 'project',
            'name' => 'Kwame Asante',
            'email' => 'kwame@example.com',
            'organisation' => 'Asante Logistics',
            'project_type' => 'new_product',
            'budget_range' => 'not_sure',
            'timeframe' => 'flexible',
            'message' => 'We need a fleet tracking dashboard connected to GPS devices.',
            'consent' => '1',
        ], $this->spamFields(), $overrides);
    }

    public function test_project_enquiry_is_stored_then_notifications_are_sent(): void
    {
        $this->setting(['notifications.enquiry_recipients' => ['hello@dxcovr.test']]);
        $service = Service::factory()->published()->create();

        $this->post('/contact', $this->enquiry(['service_id' => $service->id]))
            ->assertRedirect(route('contact.thanks'));

        $enquiry = Enquiry::query()->firstOrFail();
        $this->assertSame(EnquiryStatus::New, $enquiry->status);
        $this->assertSame($service->id, $enquiry->service_id);
        $this->assertNotNull($enquiry->consent_given_at);
        $this->assertNull($enquiry->read_at);

        Notification::assertSentOnDemand(NewEnquiryReceived::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'hello@dxcovr.test');
        Notification::assertSentOnDemand(EnquiryAcknowledgement::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'kwame@example.com');

        $this->get(route('contact.thanks'))->assertOk()->assertSee($enquiry->reference);
    }

    public function test_thank_you_page_is_not_shown_without_a_submission(): void
    {
        $this->get(route('contact.thanks'))->assertRedirect(route('contact'));
    }

    public function test_acknowledgement_can_be_switched_off(): void
    {
        $this->setting(['notifications.enquiry_acknowledgement' => false]);

        $this->post('/contact', $this->enquiry());

        Notification::assertNotSentTo(new AnonymousNotifiable, EnquiryAcknowledgement::class);
        Notification::assertNothingSentTo(new AnonymousNotifiable);
    }

    public function test_enquiry_validation_and_consent(): void
    {
        $this->post('/contact', $this->enquiry(['email' => 'bad', 'message' => 'hi', 'consent' => null, 'budget_range' => 'a-million']))
            ->assertSessionHasErrors(['email', 'message', 'consent', 'budget_range']);

        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_draft_services_cannot_be_selected(): void
    {
        $draft = Service::factory()->draft()->create();

        $this->post('/contact', $this->enquiry(['service_id' => $draft->id]))->assertSessionHasErrors('service_id');
    }

    public function test_honeypot_and_timing_checks_block_spam(): void
    {
        $this->post('/contact', $this->enquiry(['company_website' => 'https://buy-now.example']))->assertSessionHasErrors('form');

        $tooFast = array_merge($this->enquiry(), ['_form_started' => Crypt::encryptString((string) now()->getTimestamp())]);
        $this->post('/contact', $tooFast)->assertSessionHasErrors('form');

        $missing = $this->enquiry();
        unset($missing['_form_started']);
        $this->post('/contact', $missing)->assertSessionHasErrors('form');

        $this->assertDatabaseCount('enquiries', 0);
        Notification::assertNothingSent();
    }

    public function test_contact_form_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', $this->enquiry());
        }

        $this->post('/contact', $this->enquiry())->assertStatus(429);
        $this->assertDatabaseCount('enquiries', 5);
    }

    public function test_requests_without_csrf_token_are_rejected(): void
    {
        // Laravel skips CSRF checks while running tests; enforce them for this test only.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app->make('encrypter')) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $this->post('/contact', $this->enquiry())->assertStatus(419);
        $this->post('/admin/login', ['email' => 'a@b.co', 'password' => 'x'])->assertStatus(419);

        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_product_demo_request_requires_a_published_product(): void
    {
        $product = Product::factory()->published()->create();

        $this->post('/contact', $this->enquiry(['type' => 'demo', 'product_id' => $product->id]))->assertRedirect(route('contact.thanks'));
        $this->assertSame($product->id, Enquiry::query()->first()->product_id);

        $this->post('/contact', $this->enquiry(['type' => 'demo', 'product_id' => null]))->assertSessionHasErrors('product_id');
    }

    public function test_admin_can_triage_enquiries_and_viewing_marks_them_read(): void
    {
        $enquiry = Enquiry::factory()->create();
        $editorWithInbox = User::factory()->withRole('Administrator')->create();
        $this->actingAs($editorWithInbox);

        $this->get(route('admin.enquiries.show', $enquiry))->assertOk()->assertSee($enquiry->message);
        $this->assertNotNull($enquiry->fresh()->read_at);

        $this->patch(route('admin.enquiries.update', $enquiry), ['status' => 'responded', 'assigned_to' => $editorWithInbox->id, 'internal_notes' => 'Called back'])
            ->assertSessionHasNoErrors();

        $this->assertSame(EnquiryStatus::Responded, $enquiry->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'enquiries.updated', 'auditable_id' => $enquiry->id]);
    }

    public function test_analyst_viewing_enquiries_does_not_mark_them_read_or_change_them(): void
    {
        $enquiry = Enquiry::factory()->create();
        $this->actingAsRole('Read-only Analyst');

        $this->get(route('admin.enquiries.show', $enquiry))->assertOk();
        $this->assertNull($enquiry->fresh()->read_at);
        $this->patch(route('admin.enquiries.update', $enquiry), ['status' => 'closed'])->assertForbidden();
        $this->get(route('admin.enquiries.export'))->assertForbidden();
    }

    public function test_enquiries_export_to_csv(): void
    {
        Enquiry::factory()->create(['name' => 'Export Me']);
        $this->actingAsRole('Administrator');

        $response = $this->get(route('admin.enquiries.export'));

        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Export Me', $response->streamedContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'export.enquiries']);
    }

    public function test_newsletter_requires_double_opt_in(): void
    {
        $this->setting(['newsletter.enabled' => true]);

        $this->from('/insights')->post('/newsletter', array_merge(['email' => 'Reader@Example.com', 'consent' => '1'], $this->spamFields()))
            ->assertRedirect('/insights')
            ->assertSessionHas('newsletter_status');

        $subscriber = NewsletterSubscriber::query()->firstOrFail();
        $this->assertSame('reader@example.com', $subscriber->email);
        $this->assertSame(SubscriberStatus::Pending, $subscriber->status);
        Notification::assertSentOnDemand(ConfirmNewsletterSubscription::class);

        $this->get(route('newsletter.confirm', $subscriber->token))->assertOk()->assertSee('You are subscribed');
        $this->assertSame(SubscriberStatus::Subscribed, $subscriber->fresh()->status);

        $this->get(route('newsletter.unsubscribe', $subscriber->token))->assertOk()->assertSee('Unsubscribe from our newsletter?');
        $this->post(route('newsletter.unsubscribe.confirm', $subscriber->token))->assertOk();
        $this->assertSame(SubscriberStatus::Unsubscribed, $subscriber->fresh()->status);
    }

    public function test_newsletter_is_unavailable_when_disabled(): void
    {
        $this->post('/newsletter', array_merge(['email' => 'reader@example.com', 'consent' => '1'], $this->spamFields()))->assertNotFound();
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }

    public function test_newsletter_does_not_reveal_existing_subscriptions(): void
    {
        $this->setting(['newsletter.enabled' => true]);
        $subscriber = NewsletterSubscriber::query()->create(['email' => 'known@example.com']);
        $subscriber->status = SubscriberStatus::Subscribed;
        $subscriber->save();

        $this->post('/newsletter', array_merge(['email' => 'known@example.com', 'consent' => '1'], $this->spamFields()))
            ->assertSessionHas('newsletter_status', 'Thanks! Please check your inbox and confirm your subscription.');

        Notification::assertNothingSent();
    }
}
