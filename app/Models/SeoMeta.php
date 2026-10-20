<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Table(name: 'seo_meta')]
#[Fillable(['meta_title', 'meta_description', 'canonical_url', 'og_title', 'og_description', 'og_image_id', 'noindex'])]
class SeoMeta extends Model
{
    protected function casts(): array
    {
        return [
            'noindex' => 'boolean',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }
}
