<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use App\Models\PageSection;
use App\Support\Sections\SectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Adds, edits, toggles and removes predefined sections on a page. Section
 * data is validated against the schema in SectionRegistry, so editors can
 * only change approved content, never layout code or raw HTML/JavaScript.
 */
class PageSectionController extends AdminController
{
    public function store(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(SectionRegistry::definitions()))],
        ]);

        $section = $page->sections()->create([
            'type' => $data['type'],
            'sort_order' => (int) $page->sections()->max('sort_order') + 1,
            'is_enabled' => false,
            'data' => SectionRegistry::defaults($data['type']),
        ]);

        $this->audit()->record('content.section_added', $page, 'Added a '.$section->typeLabel().' section to “'.$page->title.'”');

        return redirect()->to(route('admin.pages.edit', $page).'#section-'.$section->id)
            ->with('status', 'Section added. It stays hidden until you enable it.')
            ->with('open_section', $section->id);
    }

    public function update(Request $request, PageSection $section): RedirectResponse
    {
        // Fields are namespaced per section (sections[ID][…]) so old input and errors never leak between forms.
        $prefix = 'sections.'.$section->id.'.';
        $rules = [$prefix.'label' => ['nullable', 'string', 'max:120'], $prefix.'is_enabled' => ['boolean']];

        foreach (SectionRegistry::rules($section->type) as $key => $rule) {
            $rules[$prefix.$key] = $rule;
        }

        $input = (array) $request->input('sections.'.$section->id, []);
        $input['data'] = SectionRegistry::withoutBlankRows($section->type, (array) ($input['data'] ?? []));
        $request->merge(['sections' => [$section->id => $input]]);

        $validated = $request->validate($rules, [], SectionRegistry::attributes($section->type, $prefix.'data.'))['sections'][$section->id] ?? [];

        $section->label = $validated['label'] ?? null;
        $section->is_enabled = (bool) ($validated['is_enabled'] ?? false);
        $section->data = SectionRegistry::normalise($section->type, $validated['data'] ?? []);
        $section->save();

        $section->loadMissing('page');
        $this->audit()->record('content.section_updated', $section->page, 'Updated the '.$section->typeLabel().' section on “'.$section->page->title.'”');

        return redirect()->to(route('admin.pages.edit', $section->page).'#section-'.$section->id)
            ->with('status', 'Section saved.')
            ->with('open_section', $section->id);
    }

    public function toggle(PageSection $section): RedirectResponse
    {
        $section->is_enabled = ! $section->is_enabled;
        $section->save();

        $section->loadMissing('page');

        return redirect()->to(route('admin.pages.edit', $section->page).'#section-'.$section->id)
            ->with('status', $section->is_enabled ? 'Section shown on the website.' : 'Section hidden.');
    }

    public function destroy(PageSection $section): RedirectResponse
    {
        $page = $section->page;
        $label = $section->displayLabel();
        $section->delete();

        $this->audit()->record('content.section_deleted', $page, 'Removed the section “'.$label.'” from “'.$page->title.'”');

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Section removed.');
    }
}
