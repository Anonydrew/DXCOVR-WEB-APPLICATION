@extends('layouts.site')

@section('content')
    @include('site.partials.preview-banner', ['status' => $page->status->label()])

    <x-site.page-hero
        :eyebrow="$page->template === 'legal' ? 'Legal' : null"
        :title="$page->title"
        :lede="$page->excerpt"
        :breadcrumbs="$seo->breadcrumbItems()"
    />

    @if ($body !== '')
        <section class="surface-light section">
            <div class="container-dx grid gap-12 lg:grid-cols-12">
                @if (count($toc) >= 3)
                    <nav class="hidden lg:col-span-3 lg:block" aria-label="On this page">
                        <div class="sticky top-28" data-toc>
                            <p class="mono-label text-paper-muted">On this page</p>
                            <ul class="mt-4 space-y-1 border-l border-paper-line text-sm">
                                @foreach ($toc as $entry)
                                    <li><a href="#{{ $entry['id'] }}" class="toc-link -ml-px block border-l-2 border-transparent py-1.5 text-paper-muted hover:text-ink-text {{ $entry['level'] === 3 ? 'pl-7' : 'pl-4' }}">{{ $entry['text'] }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </nav>
                @endif
                <div class="prose-dx {{ count($toc) >= 3 ? 'lg:col-span-8 lg:col-start-5' : 'lg:col-span-8 lg:col-start-3' }}">
                    {!! $body !!}
                    @if ($page->template === 'legal')
                        <p class="text-sm text-paper-muted">Last updated {{ $page->updated_at->format('j F Y') }}.</p>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @include('site.sections._render')
@endsection
