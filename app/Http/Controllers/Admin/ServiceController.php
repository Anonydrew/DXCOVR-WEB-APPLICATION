<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends AdminController
{
    public function __construct(protected HtmlSanitizer $sanitizer) {}

    public function index(Request $request): View
    {
        return view('admin.services.index', [
            'services' => Service::query()->withCount(['projects', 'products'])->ordered()->get(),
            'canManage' => $request->user()->can('services.manage'),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', ['service' => new Service(['icon' => 'software'])]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = new Service;
        $service->sort_order = (int) Service::query()->max('sort_order') + 1;

        $warning = DB::transaction(function () use ($request, $service) {
            $this->fill($service, $request);
            $service->save();

            return $this->saveSeo($service, $request->validated('seo'));
        });

        $this->audit()->record('content.created', $service, 'Created service “'.$service->title.'”');

        return $this->saved('admin.services.edit', [$service], 'Service saved as a draft.', $warning);
    }

    public function edit(Service $service): View
    {
        $service->load(['heroImage', 'loopVideo', 'seo.ogImage', 'editor']);

        return view('admin.services.form', ['service' => $service]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $warning = DB::transaction(function () use ($request, $service) {
            $this->fill($service, $request);
            $service->save();

            return $this->saveSeo($service, $request->validated('seo'));
        });

        $this->audit()->record('content.updated', $service, 'Updated service “'.$service->title.'”', $this->audit()->changes($service));

        return $this->saved('admin.services.edit', [$service], 'Service updated.', $warning);
    }

    public function destroy(Service $service): RedirectResponse
    {
        $title = $service->title;
        $service->delete();

        $this->audit()->record('content.deleted', null, 'Deleted service “'.$title.'”');

        return redirect()->route('admin.services.index')->with('status', 'Service deleted.');
    }

    protected function fill(Service $service, ServiceRequest $request): void
    {
        $service->fill($request->safe()->only([
            'title', 'slug', 'icon', 'short_description', 'hero_media_id', 'hero_video_url', 'loop_video_media_id', 'cta_heading', 'cta_body', 'cta_label', 'cta_url',
        ]));
        $service->body = $this->sanitizer->rich($request->validated('body'));
        $service->capabilities = $request->validated('capabilities') ?: null;
        $service->use_cases = $request->validated('use_cases') ?: null;
        $service->process_steps = $request->validated('process_steps') ?: null;
        $service->is_featured = $request->boolean('is_featured');
    }
}
