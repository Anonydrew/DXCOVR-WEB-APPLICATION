<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use App\Models\Enquiry;
use App\Models\User;
use App\Support\CsvExport;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnquiryController extends AdminController
{
    public function index(Request $request): View
    {
        return view('admin.enquiries.index', [
            'enquiries' => $this->filtered($request)->with(['service', 'product', 'assignee'])->latest()->paginate(25)->withQueryString(),
            'statusCounts' => Enquiry::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'unreadCount' => Enquiry::query()->unread()->count(),
        ]);
    }

    public function show(Request $request, Enquiry $enquiry): View
    {
        if ($enquiry->read_at === null && $request->user()->can('enquiries.update')) {
            $enquiry->read_at = now();
            $enquiry->save();
        }

        $enquiry->load(['service', 'product', 'assignee']);

        return view('admin.enquiries.show', [
            'enquiry' => $enquiry,
            'assignees' => User::query()->active()->orderBy('name')->get()->filter(fn (User $user) => $user->can('enquiries.view'))->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
            'mark_unread' => ['nullable', 'boolean'],
        ]);

        $enquiry->status = EnquiryStatus::from($data['status']);
        $enquiry->assigned_to = $data['assigned_to'] ?? null;
        $enquiry->internal_notes = $data['internal_notes'] ?? null;

        if ($request->boolean('mark_unread')) {
            $enquiry->read_at = null;
        }

        $enquiry->save();

        $this->audit()->record('enquiries.updated', $enquiry, 'Updated enquiry '.$enquiry->reference, $this->audit()->changes($enquiry));

        return $request->boolean('mark_unread')
            ? redirect()->route('admin.enquiries.index')->with('status', 'Marked as unread.')
            : back()->with('status', 'Enquiry updated.');
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        $reference = $enquiry->reference;
        $enquiry->delete();

        $this->audit()->record('enquiries.deleted', null, 'Deleted enquiry '.$reference);

        return redirect()->route('admin.enquiries.index')->with('status', 'Enquiry deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->audit()->record('export.enquiries', null, 'Exported enquiries to CSV', ['filters' => $request->only(['status', 'type', 'q', 'unread'])]);

        $rows = $this->filtered($request)->with(['service', 'product'])->latest()->lazy(200)->map(fn (Enquiry $enquiry) => [
            $enquiry->reference,
            $enquiry->created_at,
            $enquiry->type->label(),
            $enquiry->status->label(),
            $enquiry->name,
            $enquiry->organisation,
            $enquiry->email,
            $enquiry->phone,
            $enquiry->service?->title,
            $enquiry->product?->name,
            $enquiry->projectTypeLabel(),
            $enquiry->budgetLabel(),
            $enquiry->timeframeLabel(),
            $enquiry->message,
        ]);

        return CsvExport::download('enquiries-'.now()->format('Y-m-d').'.csv', [
            'Reference', 'Received', 'Type', 'Status', 'Name', 'Organisation', 'Email', 'Phone', 'Service', 'Product', 'Project type', 'Budget', 'Timeframe', 'Message',
        ], $rows);
    }

    protected function filtered(Request $request): Builder
    {
        return Enquiry::query()
            ->when(in_array($request->query('status'), EnquiryStatus::values(), true), fn (Builder $query) => $query->where('status', $request->query('status')))
            ->when(in_array($request->query('type'), EnquiryType::values(), true), fn (Builder $query) => $query->where('type', $request->query('type')))
            ->when($request->boolean('unread'), fn (Builder $query) => $query->unread())
            ->search($request->string('q')->limit(100, '')->toString() ?: null);
    }
}
