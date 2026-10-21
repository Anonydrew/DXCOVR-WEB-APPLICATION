<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Admin\ClientRequest;
use App\Models\Client;
use App\Models\ClientQuestionnaire;
use App\Models\Enquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Billing clients: the customers we send invoices to.
 */
class ClientController extends AdminController
{
    public function index(Request $request): View
    {
        return view('admin.clients.index', [
            'clients' => Client::query()
                ->withCount('invoices')
                ->with(['invoices' => fn ($query) => $query->whereIn('status', InvoiceStatus::open())->select(['id', 'client_id', 'currency', 'total', 'amount_paid', 'due_date', 'status'])])
                ->search($request->string('q')->limit(100, '')->toString() ?: null)
                ->orderByRaw('coalesce(company, name)')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $client = new Client;

        if ($enquiry = Enquiry::query()->find($request->integer('enquiry'))) {
            $client->fill(['name' => $enquiry->name, 'email' => $enquiry->email, 'company' => $enquiry->organisation, 'phone' => $enquiry->phone]);
        } elseif ($questionnaire = ClientQuestionnaire::query()->find($request->integer('questionnaire'))) {
            $client->fill([
                'name' => $questionnaire->contactName(),
                'email' => $questionnaire->answer('about.contact_email') ?: $questionnaire->client_email,
                'company' => $questionnaire->answer('about.company') ?: $questionnaire->client_company,
            ]);
        }

        return view('admin.clients.form', ['client' => $client]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = Client::query()->create($request->validated());

        $this->audit()->record('client.created', $client, 'Added the client '.$client->displayName());

        return redirect()->route('admin.clients.show', $client)->with('status', 'Client added. You can now create invoices for them.');
    }

    public function show(Client $client): View
    {
        return view('admin.clients.show', [
            'client' => $client,
            'invoices' => $client->invoices()->latest('issue_date')->latest('id')->paginate(25),
            'balances' => $client->balances(),
        ]);
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.form', ['client' => $client]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->fill($request->validated())->save();

        $this->audit()->record('client.updated', $client, 'Updated the client '.$client->displayName(), $this->audit()->changes($client));

        return redirect()->route('admin.clients.show', $client)->with('status', 'Client saved. Invoices already sent keep the details they were issued with.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->invoices()->exists()) {
            return back()->with('error', 'This client has invoices, so it cannot be deleted. Delete or void the invoices first if they were created by mistake.');
        }

        $name = $client->displayName();
        $client->delete();

        $this->audit()->record('client.deleted', null, 'Deleted the client '.$name);

        return redirect()->route('admin.clients.index')->with('status', 'Client deleted.');
    }
}
