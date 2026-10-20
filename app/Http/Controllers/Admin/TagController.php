<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tag;

class TagController extends TaxonomyController
{
    protected function model(): string
    {
        return Tag::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.tags';
    }

    protected function singular(): string
    {
        return 'tag';
    }

    protected function plural(): string
    {
        return 'Tags';
    }

    protected function usageRelation(): ?string
    {
        return 'articles';
    }

    protected function description(): ?string
    {
        return 'Tags are created automatically when you add them to an article. Rename or remove them here.';
    }

    protected function tabs(): array
    {
        return [
            ['label' => 'Categories', 'route' => 'admin.article-categories.index'],
            ['label' => 'Tags', 'route' => 'admin.tags.index'],
        ];
    }
}
