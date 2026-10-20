@extends('layouts.admin')

@section('title', $project->exists ? 'Edit case study' : 'New case study')

@section('content')
    <x-admin.page-header :title="$project->exists ? $project->title : 'New case study'" :back="route('admin.projects.index')">
        <x-slot:actions>
            @if ($project->exists)
                <x-admin.delete-button :action="route('admin.projects.destroy', $project)" confirm="Delete this case study permanently? Gallery links will be removed; media files stay in the library." />
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="POST" action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}" class="space-y-6" data-dirty-guard>
            @csrf
            @if ($project->exists) @method('PUT') @endif

            <div class="a-card a-card-body space-y-5">
                <x-admin.input name="title" label="Project title" :value="$project->title" required maxlength="200" />
                @include('admin.partials.slug', ['model' => $project, 'prefix' => '/work/'])
                <x-admin.textarea name="summary" label="Summary" :value="$project->summary" required rows="3" counter="600" recommended="220" />
                <x-admin.input name="tagline" label="Tagline (optional)" :value="$project->tagline" maxlength="90" counter="90"
                    hint="One short line shown under the title on the homepage, e.g. “Booking app for a family clinic”. Without one, the first sentence of the summary is used." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.select name="project_type" label="Project type" :options="\App\Enums\ProjectType::options()" :value="$project->project_type" required hint="Shown as a label so visitors know what was delivered for clients." />
                    <x-admin.select name="delivery_status" label="Project status" :options="\App\Enums\DeliveryStatus::options()" :value="$project->delivery_status" placeholder="Not specified" />
                    <x-admin.select name="project_category_id" label="Category" :options="$categories" :value="$project->project_category_id" placeholder="Uncategorised" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-admin.input name="year" label="Year" type="number" :value="$project->year" min="1990" max="{{ now()->year + 1 }}" />
                        <x-admin.input name="duration" label="Duration" :value="$project->duration" placeholder="e.g. 4 months" maxlength="80" />
                    </div>
                </div>
            </div>

            <div class="a-card a-card-body space-y-5" x-data="{ confidential: {{ old('is_confidential', $project->is_confidential) ? 'true' : 'false' }} }">
                <h2 class="a-card-title">Client & disclosure</h2>
                <x-admin.input name="client_name" label="Client or project owner" :value="$project->client_name" maxlength="160" hint="Only enter a client name you have permission to publish." />
                <div @change="confidential = $event.target.checked" >
                    <x-admin.toggle name="is_confidential" label="Confidential: hide the client name" :checked="$project->is_confidential" />
                </div>
                <div x-show="confidential" x-cloak>
                    <x-admin.input name="anonymised_client_label" label="Anonymised description" :value="$project->anonymised_client_label" placeholder="e.g. A healthcare provider in Accra" maxlength="160" />
                </div>
                <x-admin.input name="external_url" label="External project link" type="url" :value="$project->external_url" />
            </div>

            <div class="a-card a-card-body space-y-6">
                <h2 class="a-card-title">Case study</h2>
                <x-admin.media-field name="cover_media_id" label="Cover image" :media="$project->cover" />
                <x-admin.rich-editor name="challenge" label="The challenge" :value="$project->challenge" compact />
                <x-admin.rich-editor name="objectives" label="Objectives" :value="$project->objectives" compact />
                <x-admin.rich-editor name="approach" label="Process & approach" :value="$project->approach" />
                <x-admin.rich-editor name="solution" label="The solution" :value="$project->solution" />
                <x-admin.repeater name="features" label="Features" :rows="$project->features ?? []" :fields="['title' => ['label' => 'Feature'], 'body' => ['label' => 'Description', 'type' => 'textarea']]" :max="18" add-label="Add feature" />
                <x-admin.rich-editor name="role" label="Our role & team contributions" :value="$project->role" compact />
                <x-admin.rich-editor name="results" label="Results & outcomes" :value="$project->results" compact hint="Optional. Only include outcomes you can evidence; this section is hidden when empty." />
            </div>

            <div class="a-card a-card-body space-y-6">
                <h2 class="a-card-title">Media</h2>
                <x-admin.gallery-field name="gallery" label="Gallery" :attachments="$project->exists ? $project->mediaCollection('gallery') : collect()" hint="Drag to reorder. Captions are shown under each image." />
                <x-admin.list-field name="video_urls" label="Videos (YouTube or Vimeo links)" :items="$project->video_urls" placeholder="https://www.youtube.com/watch?v=…" :max="6" />
            </div>

            <div class="a-card a-card-body grid gap-5 sm:grid-cols-2">
                <h2 class="a-card-title sm:col-span-2">Relationships</h2>
                <x-admin.checkbox-list name="services" label="Related services" :options="$services" :selected="$project->services?->modelKeys() ?? []" />
                <x-admin.checkbox-list name="products" label="Related products" :options="$products" :selected="$project->products?->modelKeys() ?? []" />
                <x-admin.input name="technologies" label="Technologies" :value="$project->technologies?->pluck('name')->implode(', ')" hint="Comma separated. Used for the Work page filters." class="sm:col-span-2" list="technology-suggestions" />
                <datalist id="technology-suggestions">
                    @foreach ($technologySuggestions as $suggestion)
                        <option value="{{ $suggestion }}">
                    @endforeach
                </datalist>
                @if ($teamMembers->isNotEmpty())
                    <x-admin.checkbox-list name="team_members" label="Team members involved" :options="$teamMembers" :selected="$project->teamMembers?->modelKeys() ?? []" class="sm:col-span-2" hint="Projects linked to the founder appear on the founder profile." />
                @endif
            </div>

            <x-admin.seo-panel :seo="$project->seo" :fallback-title="$project->title" :fallback-description="$project->summary" :url="$project->exists ? route('work.show', $project) : url('/work')" />

            <div class="a-card a-card-body">
                <x-admin.toggle name="is_featured" label="Feature on the homepage" :checked="$project->is_featured" />
            </div>

            @include('admin.partials.save-bar', ['label' => $project->exists ? 'Save changes' : 'Save draft', 'note' => 'Saving never changes the publication status.'])
        </form>

        <aside>
            <x-admin.publication-panel :model="$project" type="project" />
        </aside>
    </div>
@endsection
