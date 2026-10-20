<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['media_id', 'collection', 'caption', 'sort_order'])]
class MediaAttachment extends Model
{
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
