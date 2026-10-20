<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Testimonials can only be published once consent to publish has been recorded.
 */
class TestimonialController extends AdminController
{
    public function index(Request $request): View
    {
        return view('admin.testimonials.index', [
            'testimonials' => Testimonial::query()->with('project')->orderBy('sort_order')->get(),
            'canManage' => $request->user()->can('company.manage'),
        ]);
    }

    public function create(): View
    {
        return view('admin.testimonials.form', ['testimonial' => new Testimonial, 'projects' => $this->projects()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $testimonial = new Testimonial;
        $testimonial->sort_order = (int) Testimonial::query()->max('sort_order') + 1;
        $testimonial->fill($this->validated($request))->save();

        $this->audit()->record('content.created', $testimonial, 'Added a testimonial from '.$testimonial->author_name);

        return redirect()->route('admin.testimonials.index')->with('status', 'Testimonial saved.');
    }

    public function edit(Testimonial $testimonial): View
    {
        $testimonial->load('photo');

        return view('admin.testimonials.form', ['testimonial' => $testimonial, 'projects' => $this->projects()]);
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->fill($this->validated($request))->save();

        $this->audit()->record('content.updated', $testimonial, 'Updated the testimonial from '.$testimonial->author_name, $this->audit()->changes($testimonial));

        return redirect()->route('admin.testimonials.index')->with('status', 'Testimonial updated.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $name = $testimonial->author_name;
        $testimonial->delete();

        $this->audit()->record('content.deleted', null, 'Deleted the testimonial from '.$name);

        return redirect()->route('admin.testimonials.index')->with('status', 'Testimonial deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'quote' => ['required', 'string', 'max:1200'],
            'author_name' => ['required', 'string', 'max:160'],
            'author_title' => ['nullable', 'string', 'max:160'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'photo_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'consent_confirmed' => ['boolean'],
            'is_published' => ['boolean', 'declined_if:consent_confirmed,0,false'],
        ], [
            'is_published.declined_if' => 'A testimonial can only be published after the person has given consent.',
        ]);

        $data['consent_confirmed'] = $request->boolean('consent_confirmed');
        $data['is_published'] = $request->boolean('is_published') && $data['consent_confirmed'];

        return $data;
    }

    /**
     * @return Collection<int, string>
     */
    protected function projects(): Collection
    {
        return Project::query()->orderBy('title')->pluck('title', 'id');
    }
}
