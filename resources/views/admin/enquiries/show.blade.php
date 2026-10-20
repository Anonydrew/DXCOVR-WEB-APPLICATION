@extends('layouts.admin')

@section('title', 'Enquiry · '.$enquiry->name)

@section('content')
    <x-admin.page-header :title="$enquiry->name" :description="$enquiry->type->label().' · '.$enquiry->created_at->format('j M Y, H:i')" :back="route('admin.enquiries.index')">
        <x-slot:actions>
            <a href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: your enquiry to '.app(\App\Services\SiteSettings::class)->get('site.name')) }}" class="a-btn a-btn-primary"><x-icon name="mail" :size="16" /> Reply by email</a>
            @can('onboarding.manage')
                <a href="{{ route('admin.onboarding.create', ['enquiry' => $enquiry->id]) }}" class="a-btn"><x-icon name="sparkle" :size="16" /> Send onboarding questionnaire</a>
            @endcan
            @can('enquiries.delete')
                <x-admin.delete-button :action="route('admin.enquiries.destroy', $enquiry)" confirm="Delete this enquiry permanently?" />
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-6">
            <section class="a-card" aria-labelledby="message-heading">
                <div class="a-card-header"><h2 id="message-heading" class="a-card-title">Message</h2><x-admin.status-badge :status="$enquiry->status" /></div>
                <div class="a-card-body whitespace-pre-line text-sm leading-relaxed">{{ $enquiry->message }}</div>
            </section>
            <section class="a-card" aria-labelledby="details-heading">
                <div class="a-card-header"><h2 id="details-heading" class="a-card-title">Details</h2></div>
                <dl class="a-card-body grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="a-mono a-subtle">Email</dt><dd class="mt-1"><a href="mailto:{{ $enquiry->email }}" class="hover:text-lime">{{ $enquiry->email }}</a></dd></div>
                    <div><dt class="a-mono a-subtle">Phone</dt><dd class="mt-1">{{ $enquiry->phone ?: 'Not set' }}</dd></div>
                    <div><dt class="a-mono a-subtle">Organisation</dt><dd class="mt-1">{{ $enquiry->organisation ?: 'Not set' }}</dd></div>
                    <div><dt class="a-mono a-subtle">Service</dt><dd class="mt-1">{{ $enquiry->service?->title ?? 'Not set' }}</dd></div>
                    @if ($enquiry->product)
                        <div><dt class="a-mono a-subtle">Product</dt><dd class="mt-1">{{ $enquiry->product->name }}</dd></div>
                    @endif
                    <div><dt class="a-mono a-subtle">Project type</dt><dd class="mt-1">{{ $enquiry->projectTypeLabel() ?? 'Not set' }}</dd></div>
                    <div><dt class="a-mono a-subtle">Budget</dt><dd class="mt-1">{{ $enquiry->budgetLabel() ?? 'Not set' }}</dd></div>
                    <div><dt class="a-mono a-subtle">Timeframe</dt><dd class="mt-1">{{ $enquiry->timeframeLabel() ?? 'Not set' }}</dd></div>
                    <div><dt class="a-mono a-subtle">Submitted from</dt><dd class="mt-1 break-all">{{ $enquiry->source_url ?: 'Not set' }}</dd></div>
                    <div><dt class="a-mono a-subtle">Reference</dt><dd class="mt-1 font-mono text-xs">{{ $enquiry->reference }}</dd></div>
                </dl>
            </section>
        </div>

        <aside>
            @can('enquiries.update')
                <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}" class="a-card a-card-body space-y-4">
                    @csrf
                    @method('PATCH')
                    <h2 class="a-card-title">Manage</h2>
                    <x-admin.select name="status" label="Status" :options="\App\Enums\EnquiryStatus::options()" :value="$enquiry->status" required />
                    <x-admin.select name="assigned_to" label="Assigned to" :options="$assignees" :value="$enquiry->assigned_to" placeholder="Unassigned" />
                    <x-admin.textarea name="internal_notes" label="Internal notes" :value="$enquiry->internal_notes" rows="5" hint="Never shared with the sender." />
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="a-btn a-btn-primary">Save</button>
                        <button type="submit" name="mark_unread" value="1" class="a-btn a-btn-ghost">Mark as unread</button>
                    </div>
                </form>
            @endcan
        </aside>
    </div>
@endsection
