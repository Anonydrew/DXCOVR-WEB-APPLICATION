<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\ArticleCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'description', 'sort_order'])]
class ArticleCategory extends Model
{
    /** @use HasFactory<ArticleCategoryFactory> */
    use HasFactory, HasSlug;

    public function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }
}
