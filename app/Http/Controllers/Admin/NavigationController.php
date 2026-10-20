<?php

namespace App\Http\Controllers\Admin;

use App\Models\NavigationItem;
use App\Models\NavigationMenu;
use App\Rules\SafeUrl;
use App\Services\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NavigationController extends AdminController
{
    public function __construct(protected Navigation $navigation) {}

    public function index(): View
    {
        foreach (NavigationMenu::LOCATIONS as $key => $name) {
            NavigationMenu::query()->firstOrCreate(['key' => $key], ['name' => $name]);
        }

        return view('admin.navigation.index', [
            'menus' => NavigationMenu::query()->withCount('items')->get()->sortBy(fn (NavigationMenu $menu) => array_search($menu->key, array_keys(NavigationMenu::LOCATIONS), true)),
        ]);
    }

    public function edit(NavigationMenu $menu): View
    {
        $menu->load(['rootItems.children.children']);

        return view('admin.navigation.edit', [
            'menu' => $menu,
            'parents' => $menu->rootItems->pluck('label', 'id'),
            'allowsChildren' => $menu->key === 'primary',
        ]);
    }

    public function store(Request $request, NavigationMenu $menu): RedirectResponse
    {
        $data = $this->validated($request, $menu);
        $data['sort_order'] = (int) $menu->items()->where('parent_id', $data['parent_id'] ?? null)->max('sort_order') + 1;

        $item = $menu->items()->create($data);
        $this->navigation->flush();

        $this->audit()->record('content.navigation_added', $menu, 'Added “'.$item->label.'” to '.$menu->name);

        return redirect()->route('admin.navigation.edit', $menu)->with('status', 'Menu item added.');
    }

    public function update(Request $request, NavigationItem $item): RedirectResponse
    {
        $menu = $item->menu;
        $data = $this->validated($request, $menu, $item);

        $item->update($data);
        $this->navigation->flush();

        $this->audit()->record('content.navigation_updated', $menu, 'Updated menu item “'.$item->label.'” in '.$menu->name);

        return redirect()->route('admin.navigation.edit', $menu)->with('status', 'Menu item updated.');
    }

    public function destroy(NavigationItem $item): RedirectResponse
    {
        $menu = $item->menu;
        $label = $item->label;
        $item->delete();
        $this->navigation->flush();

        $this->audit()->record('content.navigation_removed', $menu, 'Removed “'.$label.'” from '.$menu->name);

        return redirect()->route('admin.navigation.edit', $menu)->with('status', 'Menu item removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, NavigationMenu $menu, ?NavigationItem $item = null): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'url' => ['required', 'string', 'max:500', new SafeUrl],
            'description' => ['nullable', 'string', 'max:200'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('navigation_items', 'id')->where('navigation_menu_id', $menu->id)->whereNull('parent_id'),
                Rule::notIn(array_filter([$item?->id])),
            ],
            'open_in_new_tab' => ['boolean'],
            'is_enabled' => ['boolean'],
        ]);

        if ($item && $item->children()->exists()) {
            $data['parent_id'] = null;
        }

        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');
        $data['is_enabled'] = $request->boolean('is_enabled');

        return $data;
    }
}
