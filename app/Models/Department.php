<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'sort_order'])]
class Department extends Model
{
    use HasSlug;

    public function slugSourceAttribute(): string
    {
        return 'name';
    }

    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class);
    }
}
