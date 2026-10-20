<div class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center gap-3 border-t border-line bg-ink/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <button type="submit" class="a-btn a-btn-primary">{{ $label ?? 'Save changes' }}</button>
    @isset($note)
        <p class="text-xs a-subtle">{{ $note }}</p>
    @endisset
</div>
