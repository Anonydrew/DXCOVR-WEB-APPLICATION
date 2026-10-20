<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name'])]
class NavigationMenu extends Model
{
    public const LOCATIONS = [
        'primary' => 'Main navigation',
        'footer_explore' => 'Footer: Explore',
        'footer_company' => 'Footer: Company',
        'footer_legal' => 'Footer: Legal',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('sort_order');
    }

    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }
}
