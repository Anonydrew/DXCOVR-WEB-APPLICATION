<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Shared CRUD for simple name/slug lists. Each subclass declares its model,
 * route prefix, labels and whether it has descriptions or ordering.
 * Permissions are enforced by the route middleware.
 */
abstract class TaxonomyController extends AdminController
{
    /**
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    abstract protected function routePrefix(): string;

    abstract protected function singular(): string;

    abstract protected function plural(): string;

    protected function hasDescription(): bool
    {
        return false;
    }

    protected function sortable(): ?string
    {
        return null;
    }

    protected function usageRelation(): ?string
    {
        return null;
    }

    protected function description(): ?string
    {
        return null;
    }

    /**
     * @return list<array{label: string, route: string}>
     */
    protected function tabs(): array
    {
        return [];
    }

    public function index(): View
    {
        $model = $this->model();
        $query = $model::query();

        if ($relation = $this->usageRelation()) {
            $query->withCount($relation);
        }

        $query = $this->sortable() ? $query->orderBy('sort_order')->orderBy('name') : $query->orderBy('name');

        return view('admin.taxonomy.index', [
            'items' => $query->get(),
            'routePrefix' => $this->routePrefix(),
            'singular' => $this->singular(),
            'plural' => $this->plural(),
            'hasDescription' => $this->hasDescription(),
            'sortable' => $this->sortable(),
            'usageRelation' => $this->usageRelation(),
            'description' => $this->description(),
            'tabs' => $this->tabs(),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route($this->routePrefix().'.index');
    }

    public function edit(): RedirectResponse
    {
        return redirect()->route($this->routePrefix().'.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $model = $this->model();
        $data = $this->validated($request, null);

        $item = new $model;
        $item->fill($data);

        if ($this->sortable()) {
            $item->sort_order = (int) $model::query()->max('sort_order') + 1;
        }

        $item->save();

        $this->audit()->record('content.created', $item, 'Created '.$this->singular().' “'.$item->name.'”');

        return redirect()->route($this->routePrefix().'.index')->with('status', ucfirst($this->singular()).' added.');
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $item = $this->find($id);
        $item->fill($this->validated($request, $item->getKey()));
        $item->save();

        $this->audit()->record('content.updated', $item, 'Updated '.$this->singular().' “'.$item->name.'”');

        return redirect()->route($this->routePrefix().'.index')->with('status', ucfirst($this->singular()).' updated.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $item = $this->find($id);
        $name = $item->name;
        $item->delete();

        $this->audit()->record('content.deleted', null, 'Deleted '.$this->singular().' “'.$name.'”');

        return redirect()->route($this->routePrefix().'.index')->with('status', ucfirst($this->singular()).' deleted.');
    }

    protected function find(string $id): Model
    {
        $model = $this->model();

        return $model::query()->findOrFail((int) $id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, int|string|null $ignoreId): array
    {
        $table = (new ($this->model()))->getTable();

        $rules = [
            'name' => ['required', 'string', 'max:120', Rule::unique($table, 'name')->ignore($ignoreId)],
            'slug' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($table, 'slug')->ignore($ignoreId)],
        ];

        if ($this->hasDescription()) {
            $rules['description'] = ['nullable', 'string', 'max:600'];
        }

        return $request->validate($rules, [
            'slug.regex' => 'The slug may contain only lower-case letters, numbers and single hyphens.',
        ]);
    }
}
