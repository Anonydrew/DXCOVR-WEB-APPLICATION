{{-- Slug input that follows the title field until edited. Expects $model, $prefix and optional $source. --}}
<div x-data="slugField('{{ $source ?? 'f-title' }}', {{ $model->exists ? 'true' : 'false' }})">
    <label for="f-slug" class="a-label">URL slug</label>
    <div class="flex items-center gap-2">
        <span class="whitespace-nowrap text-sm a-subtle">{{ $prefix }}</span>
        <input id="f-slug" name="slug" x-ref="slug" @change="manual()" value="{{ old('slug', $model->slug) }}" class="a-input" maxlength="180" @error('slug') aria-invalid="true" aria-describedby="f-slug-error" @enderror>
    </div>
    <p class="a-hint">Leave blank to generate from the title. Changing a published URL breaks existing links, so add a redirect if you do.</p>
    @error('slug')<p id="f-slug-error" class="a-error">{{ $message }}</p>@enderror
</div>
