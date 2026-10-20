<?php

namespace App\Http\Controllers\Admin;

use App\Models\ProjectCategory;

class ProjectCategoryController extends TaxonomyController
{
    protected function model(): string
    {
        return ProjectCategory::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.project-categories';
    }

    protected function singular(): string
    {
        return 'project category';
    }

    protected function plural(): string
    {
        return 'Project categories';
    }

    protected function hasDescription(): bool
    {
        return true;
    }

    protected function sortable(): ?string
    {
        return 'project-categories';
    }

    protected function usageRelation(): ?string
    {
        return 'projects';
    }

    protected function description(): ?string
    {
        return 'Used to group and filter case studies on the Work page.';
    }

    protected function tabs(): array
    {
        return [
            ['label' => 'Case studies', 'route' => 'admin.projects.index'],
            ['label' => 'Categories', 'route' => 'admin.project-categories.index'],
            ['label' => 'Technologies', 'route' => 'admin.technologies.index'],
        ];
    }
}
