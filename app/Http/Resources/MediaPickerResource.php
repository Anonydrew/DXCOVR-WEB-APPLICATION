<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Media
 */
class MediaPickerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->displayName(),
            'title' => $this->title,
            'alt' => $this->alt,
            'url' => $this->isImage() ? $this->variantUrl(1600) : $this->url(),
            'original_url' => $this->url(),
            'thumb' => $this->isImage() ? $this->variantUrl(480) : null,
            'is_image' => $this->isImage(),
            'kind' => $this->kind->value,
            'extension' => strtoupper($this->extension),
            'size' => $this->humanSize(),
            'width' => $this->width,
            'height' => $this->height,
            'edit_url' => route('admin.media.show', $this->id),
        ];
    }
}
