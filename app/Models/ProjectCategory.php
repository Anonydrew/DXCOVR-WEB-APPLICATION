<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\ProjectCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'sort_order'])]
class ProjectCategory extends Model
{
    /** @use HasFactory<ProjectCategoryFactory> */
    use HasFactory, HasSlug;

    public function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
