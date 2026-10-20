<?php

namespace App\Models;

use App\Support\Sections\SectionRegistry;
use App\Support\Text;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A predefined, validated content block on a page. The "data" schema for each
 * type is defined in App\Support\Sections\SectionRegistry.
 */
#[Fillable(['type', 'label', 'sort_order', 'is_enabled', 'data'])]
class PageSection extends Model
{
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_enabled' => 'boolean',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function value(string $key, mixed $default = null): mixed
    {
        return data_get($this->data ?? [], $key, $default);
    }

    public function typeLabel(): string
    {
        return SectionRegistry::definition($this->type)['label'] ?? str($this->type)->headline()->toString();
    }

    public function displayLabel(): string
    {
        return $this->label ?: (Text::plain($this->value('heading')) ?: $this->typeLabel());
    }
}
