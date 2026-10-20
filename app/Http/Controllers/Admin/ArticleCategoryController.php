<?php

namespace App\Http\Controllers\Admin;

use App\Models\ArticleCategory;

class ArticleCategoryController extends TaxonomyController
{
    protected function model(): string
    {
        return ArticleCategory::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.article-categories';
    }

    protected function singular(): string
    {
        return 'category';
    }

    protected function plural(): string
    {
        return 'Article categories';
    }

    protected function hasDescription(): bool
    {
        return true;
    }

    protected function sortable(): ?string
    {
        return 'article-categories';
    }

    protected function usageRelation(): ?string
    {
        return 'articles';
    }

    protected function description(): ?string
    {
        return 'Categories organise the Insights section and create a page for each topic.';
    }

    protected function tabs(): array
    {
        return [
            ['label' => 'Categories', 'route' => 'admin.article-categories.index'],
            ['label' => 'Tags', 'route' => 'admin.tags.index'],
        ];
    }
}
