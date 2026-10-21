{{-- Invoice list. Needs $invoices (paginator, with client loaded when $showClient). --}}
<div class="overflow-x-auto">
    <table class="a-table">
        <thead>
            <tr>
                <th scope="col">Invoice</th>
                @if ($showClient ?? true)<th scope="col">Client</th>@endif
                <th scope="col">Status</th>
                <th scope="col">Issued</th>
                <th scope="col">Due</th>
                <th scope="col" class="!text-right">Total</th>
                <th scope="col" class="!text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoices as $invoice)
                <tr>
                    <td>
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="font-semibold hover:text-lime">{{ $invoice->number }}</a>
                        @if ($invoice->subject)
                            <p class="max-w-64 truncate text-xs a-subtle">{{ $invoice->subject }}</p>
                        @endif
                    </td>
                    @if ($showClient ?? true)
                        <td><a href="{{ route('admin.clients.show', $invoice->client_id) }}" class="a-muted hover:text-lime">{{ $invoice->client?->displayName() }}</a></td>
                    @endif
                    <td class="whitespace-nowrap">
                        <x-admin.status-badge :status="$invoice->status" />
                        @if ($invoice->isOverdue())
                            <span class="a-badge a-badge-danger ml-1">Overdue</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap a-muted">{{ $invoice->issue_date->format('j M Y') }}</td>
                    <td class="whitespace-nowrap {{ $invoice->isOverdue() ? 'text-danger' : 'a-muted' }}">{{ $invoice->due_date?->format('j M Y') ?? '—' }}</td>
                    <td class="whitespace-nowrap text-right tabular-nums">{{ $invoice->money($invoice->total) }}</td>
                    <td class="whitespace-nowrap text-right tabular-nums {{ $invoice->balance() > 0 && ! $invoice->isVoid() && ! $invoice->isDraft() ? '' : 'a-subtle' }}">
                        {{ $invoice->isVoid() ? '—' : $invoice->money($invoice->balance()) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
{{ $invoices->links('admin.partials.pagination') }}
