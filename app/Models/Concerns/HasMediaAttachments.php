<?php

namespace App\Models\Concerns;

use App\Models\Media;
use App\Models\MediaAttachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

/**
 * Ordered, captioned media collections (galleries, screenshots) for a model.
 */
trait HasMediaAttachments
{
    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'attachable')->orderBy('sort_order');
    }

    /**
     * @return Collection<int, MediaAttachment>
     */
    public function mediaCollection(string $collection): Collection
    {
        $attachments = $this->relationLoaded('mediaAttachments')
            ? $this->mediaAttachments
            : $this->mediaAttachments()->with('media')->get();

        return $attachments
            ->where('collection', $collection)
            ->filter(fn (MediaAttachment $attachment) => $attachment->media !== null)
            ->values();
    }

    /**
     * Replace a collection with the given ordered media items.
     *
     * @param  array<int, array{media_id: int|string, caption?: string|null}>  $items
     */
    public function syncMediaCollection(string $collection, array $items): void
    {
        $validIds = Media::query()->whereIn('id', array_column($items, 'media_id'))->pluck('id')->all();

        $this->mediaAttachments()->where('collection', $collection)->delete();

        foreach (array_values($items) as $position => $item) {
            if (! in_array((int) $item['media_id'], $validIds, true)) {
                continue;
            }

            $this->mediaAttachments()->create([
                'media_id' => (int) $item['media_id'],
                'collection' => $collection,
                'caption' => $item['caption'] ?? null,
                'sort_order' => $position,
            ]);
        }

        $this->unsetRelation('mediaAttachments');
    }
}
