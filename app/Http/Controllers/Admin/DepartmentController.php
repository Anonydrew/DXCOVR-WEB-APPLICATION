<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;

class DepartmentController extends TaxonomyController
{
    protected function model(): string
    {
        return Department::class;
    }

    protected function routePrefix(): string
    {
        return 'admin.departments';
    }

    protected function singular(): string
    {
        return 'department';
    }

    protected function plural(): string
    {
        return 'Departments';
    }

    protected function sortable(): ?string
    {
        return 'departments';
    }

    protected function usageRelation(): ?string
    {
        return 'jobListings';
    }

    protected function description(): ?string
    {
        return 'Departments group job listings and power the Careers page filter.';
    }

    protected function tabs(): array
    {
        return [
            ['label' => 'Job listings', 'route' => 'admin.jobs.index'],
            ['label' => 'Departments', 'route' => 'admin.departments.index'],
        ];
    }
}
