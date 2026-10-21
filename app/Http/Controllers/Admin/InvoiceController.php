<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\Invoicing;
use App\Services\SiteSettings;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class InvoiceController extends AdminController
{
    public function __construct(protected Invoicing $invoicing) {}

    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = $status === 'overdue' || in_array($status, InvoiceStatus::values(), true) ? $status : null;
        $client = Client::query()->find($request->integer('client'));

        return view('admin.invoices.index', [
            'invoices' => Invoice::query()
                ->with('client')
                ->when($status === 'overdue', fn (Builder $query) => $query->overdue())
                ->when($status && $status !== 'overdue', fn (Builder $query) => $query->where('status', $status))
                ->when($client, fn (Builder $query) => $query->where('client_id', $client->id))
                ->search($request->string('q')->limit(100, '')->toString() ?: null)
                ->latest('issue_date')
                ->latest('id')
                ->paginate(25)
                ->withQueryString(),
            'client' => $client,
            'statusCounts' => Invoice::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'overdueCount' => Invoice::query()->overdue()->count(),
            'outstanding' => Invoice::query()
                ->whereIn('status', InvoiceStatus::open())
                ->selectRaw('currency, sum(total - amount_paid) as balance')
                ->groupBy('currency')
                ->pluck('balance', 'currency'),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! Client::query()->exists()) {
            return redirect()->route('admin.clients.create')->with('status', 'Add the client you are invoicing first.');
        }

        $client = Client::query()->find($request->integer('client'));

        return view('admin.invoices.form', $this->formData($this->invoicing->draftFor($client)));
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoicing->save(new Invoice, $request->invoiceData(), $request->lineItems());

        $this->audit()->record('invoice.created', $invoice, 'Created invoice '.$invoice->number.' for '.$invoice->client->displayName());

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Invoice saved as a draft. Send it to your client when it is ready.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['client', 'items', 'payments.creator', 'creator']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'issuer' => $this->issuer(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function preview(Invoice $invoice): Response
    {
        return response()->view('site.invoices.show', [
            'invoice' => $invoice->load(['client', 'items', 'payments']),
            'issuer' => $this->issuer(),
            'preview' => true,
        ]);
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if ($invoice->isVoid()) {
            return redirect()->route('admin.invoices.show', $invoice)->with('error', 'Void invoices cannot be edited. Restore it first.');
        }

        return view('admin.invoices.form', $this->formData($invoice->load(['client', 'items'])));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->isVoid(), 409, 'Void invoices cannot be edited.');

        $before = $invoice->only(['number', 'total', 'client_id', 'due_date']);
        $this->invoicing->save($invoice, $request->invoiceData(), $request->lineItems());

        $this->audit()->record('invoice.updated', $invoice, 'Updated invoice '.$invoice->number, ['before' => array_map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value, $before)]);

        return redirect()->route('admin.invoices.show', $invoice)->with('status', 'Invoice saved.');
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $copy = $this->invoicing->duplicate($invoice->load(['client', 'items']));

        return redirect()->route('admin.invoices.edit', $copy)->with('status', 'Copied to a new draft, '.$copy->number.'. Check the dates and lines, then save.');
    }

    public function markSent(Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->isVoid(), 409);

        $this->invoicing->markSent($invoice);

        return back()->with('status', 'Marked as sent. Your client can now open the invoice link.');
    }

    public function email(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->isVoid(), 409);

        $email = $request->validate(['email' => ['required', 'string', 'email:rfc', 'max:255']])['email'];

        $this->invoicing->email($invoice, $email);

        return back()->with('status', 'Invoice emailed to '.$email.'.');
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $this->invoicing->void($invoice);

        return back()->with('status', 'Invoice voided. It stays on record and the client link now shows it as void.');
    }

    public function restore(Invoice $invoice): RedirectResponse
    {
        $this->invoicing->restore($invoice);

        return back()->with('status', 'Invoice restored.');
    }

    public function regenerate(Invoice $invoice): RedirectResponse
    {
        $invoice->issueToken();
        $invoice->save();

        $this->audit()->record('invoice.link_regenerated', $invoice, 'Generated a new link for invoice '.$invoice->number);

        return back()->with('status', 'A new link has been generated. The previous link no longer works.');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->isVoid(), 409);

        $request->merge(['amount' => str_replace([',', ' '], '', (string) $request->input('amount'))]);

        $data = $request->validateWithBag('payment', [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000000000'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [], ['paid_on' => 'payment date']);

        $data['amount'] = (int) Money::toMinor($data['amount']);

        $this->invoicing->recordPayment($invoice, $data);

        return back()->with('status', $invoice->isPaid() ? 'Payment recorded. This invoice is now paid in full.' : 'Payment recorded. '.$invoice->money($invoice->balance()).' is still due.');
    }

    public function destroyPayment(Invoice $invoice, InvoicePayment $payment): RedirectResponse
    {
        abort_unless($payment->invoice_id === $invoice->id, 404);

        $this->invoicing->deletePayment($invoice, $payment);

        return back()->with('status', 'Payment removed.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (! $invoice->isDraft() || $invoice->payments()->exists()) {
            return back()->with('error', 'Only unsent drafts can be deleted. Void this invoice instead so your records stay complete.');
        }

        $number = $invoice->number;
        $client = $invoice->client;
        $invoice->delete();

        $this->audit()->record('invoice.deleted', null, 'Deleted draft invoice '.$number);

        return redirect()->route('admin.clients.show', $client)->with('status', 'Draft invoice '.$number.' deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Invoice $invoice): array
    {
        return [
            'invoice' => $invoice,
            'clients' => Client::query()->orderByRaw('coalesce(company, name)')->get(['id', 'name', 'company', 'currency']),
            'currencies' => Money::CURRENCIES,
            'defaultCurrency' => (string) app(SiteSettings::class)->get('invoicing.currency', 'GHS'),
        ];
    }

    /**
     * @return array{name: string, address: ?string, email: ?string, phone: ?string, tax_number: ?string}
     */
    protected function issuer(): array
    {
        return $this->invoicing->issuer();
    }
}
