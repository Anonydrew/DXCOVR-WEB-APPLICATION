@extends('layouts.admin')

@section('title', 'Newsletter')

@section('content')
    <x-admin.page-header title="Newsletter subscribers" description="Double opt-in: people are only counted as subscribed after confirming by email. Export the list to send campaigns through your provider.">
        <x-slot:actions>
            @can('exports.run')
                <a href="{{ route('admin.newsletter.export') }}" class="a-btn"><x-icon name="download" :size="16" /> Export subscribed</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    @unless ($enabled)
        <div class="a-alert a-alert-info mb-6">
            <x-icon name="alert" :size="18" class="mt-0.5 flex-none text-info" />
            <div>The newsletter sign-up form is currently turned off.
                @can('settings.manage')
                    <a href="{{ route('admin.settings.edit', 'newsletter') }}" class="underline">Turn it on in settings</a>.
                @endcan
            </div>
        </div>
    @endunless

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach (\App\Enums\SubscriberStatus::cases() as $status)
            <a href="{{ route('admin.newsletter.index', ['status' => $status->value]) }}" class="a-card p-4 hover:border-line-strong">
                <p class="a-mono a-subtle">{{ $status->label() }}</p>
                <p class="mt-2 font-display text-3xl font-semibold">{{ $counts[$status->value] ?? 0 }}</p>
            </a>
        @endforeach
    </div>

    <div class="a-card overflow-hidden">
        <x-admin.filters :action="route('admin.newsletter.index')" placeholder="Search email addresses" />
        @if ($subscribers->isEmpty())
            <p class="p-6 text-sm a-muted">No subscribers found.</p>
        @else
            <div class="overflow-x-auto">
                <table class="a-table">
                    <thead><tr><th scope="col">Email</th><th scope="col">Status</th><th scope="col">Signed up</th><th scope="col">Source</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($subscribers as $subscriber)
                            <tr>
                                <td>{{ $subscriber->email }}</td>
                                <td><span class="a-badge {{ $subscriber->status === \App\Enums\SubscriberStatus::Subscribed ? 'a-badge-success' : 'a-badge-muted' }}">{{ $subscriber->status->label() }}</span></td>
                                <td class="a-muted">{{ $subscriber->created_at->format('j M Y') }}</td>
                                <td class="a-muted">{{ $subscriber->source ?: 'Not set' }}</td>
                                <td class="text-right">
                                    @can('newsletter.manage')
                                        <x-admin.delete-button :action="route('admin.newsletter.destroy', $subscriber)" confirm="Delete this subscriber permanently?" label="" :aria-label="'Delete '.$subscriber->email" />
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $subscribers->links('admin.partials.pagination') }}
        @endif
    </div>
@endsection
