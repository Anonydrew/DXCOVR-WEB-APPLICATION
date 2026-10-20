@extends('layouts.admin')

@section('title', $faq->exists ? 'Edit FAQ' : 'Add FAQ')

@section('content')
    <x-admin.page-header :title="$faq->exists ? 'Edit FAQ' : 'Add FAQ'" :back="route('admin.faqs.index')">
        <x-slot:actions>
            @if ($faq->exists)
                <x-admin.delete-button :action="route('admin.faqs.destroy', $faq)" />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $faq->exists ? route('admin.faqs.update', $faq) : route('admin.faqs.store') }}" class="max-w-3xl space-y-6">
        @csrf
        @if ($faq->exists) @method('PUT') @endif

        <div class="a-card a-card-body space-y-5">
            <x-admin.input name="question" label="Question" :value="$faq->question" required maxlength="255" />
            <x-admin.rich-editor name="answer" label="Answer" :value="$faq->answer" required compact />
        </div>

        <div class="a-card a-card-body grid gap-5 sm:grid-cols-3">
            <x-admin.select name="group" label="Group" :options="\App\Models\Faq::GROUPS" :value="$faq->group" required />
            <x-admin.select name="service_id" label="Show on service page" :options="$services" :value="$faq->service_id" placeholder="None" />
            <x-admin.select name="product_id" label="Show on product page" :options="$products" :value="$faq->product_id" placeholder="None" />
            <x-admin.toggle name="is_published" label="Published" :checked="$faq->is_published" class="sm:col-span-3" />
        </div>

        @include('admin.partials.save-bar', ['label' => 'Save FAQ'])
    </form>
@endsection
