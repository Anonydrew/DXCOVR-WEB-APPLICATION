@extends('layouts.admin')

@section('title', $testimonial->exists ? 'Edit testimonial' : 'Add testimonial')

@section('content')
    <x-admin.page-header :title="$testimonial->exists ? 'Edit testimonial' : 'Add testimonial'" :back="route('admin.testimonials.index')">
        <x-slot:actions>
            @if ($testimonial->exists)
                <x-admin.delete-button :action="route('admin.testimonials.destroy', $testimonial)" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($testimonial->exists) @method('PUT') @endif

        <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
            <x-admin.textarea name="quote" label="Quote" :value="$testimonial->quote" required rows="4" counter="1200" class="sm:col-span-2" hint="Use the exact words the person approved." />
            <x-admin.input name="author_name" label="Name" :value="$testimonial->author_name" required />
            <x-admin.input name="author_title" label="Job title" :value="$testimonial->author_title" />
            <x-admin.input name="organisation" label="Organisation" :value="$testimonial->organisation" />
            <x-admin.select name="project_id" label="Related project" :options="$projects" :value="$testimonial->project_id" placeholder="None" />
            <x-admin.media-field name="photo_media_id" label="Photo (optional)" :media="$testimonial->photo" class="sm:col-span-2" />
        </div>

        <div class="a-card a-card-body space-y-4">
            <x-admin.toggle name="consent_confirmed" label="Consent to publish has been obtained" :checked="$testimonial->consent_confirmed" hint="Required before the testimonial can appear on the website." />
            <x-admin.toggle name="is_published" label="Show on the website" :checked="$testimonial->is_published" />
        </div>

        @include('admin.partials.save-bar', ['label' => 'Save testimonial'])
    </form>
@endsection
