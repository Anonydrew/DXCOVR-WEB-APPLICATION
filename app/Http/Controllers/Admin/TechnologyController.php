<?php

namespace App\Http\Controllers\Admin;

use App\Models\Technology;

class TechnologyController extends TaxonomyController
{
    protected function model(): string
    {
        return Technology::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.technologies';
    }

    protected function singular(): string
    {
        return 'technology';
    }

    protected function plural(): string
    {
        return 'Technologies';
    }

    protected function usageRelation(): ?string
    {
        return 'projects';
    }

    protected function description(): ?string
    {
        return 'Technologies are added from the case study form and power the Work page technology filter.';
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
