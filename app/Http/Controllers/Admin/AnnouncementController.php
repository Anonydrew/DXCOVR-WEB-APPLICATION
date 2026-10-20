<?php

namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use App\Rules\SafeUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Site-wide announcement banner. The most recent active announcement inside
 * its display window is shown at the top of every public page.
 */
class AnnouncementController extends AdminController
{
    public function index(Request $request): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::query()->latest()->get(),
            'canManage' => $request->user()->can('company.manage'),
        ]);
    }

    public function create(): View
    {
        return view('admin.announcements.form', ['announcement' => new Announcement]);
    }

    public function store(Request $request): RedirectResponse
    {
        $announcement = Announcement::query()->create($this->validated($request));

        $this->audit()->record('content.created', $announcement, 'Created announcement “'.$announcement->title.'”');

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement saved.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.form', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        $this->audit()->record('content.updated', $announcement, 'Updated announcement “'.$announcement->title.'”', $this->audit()->changes($announcement));

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $title = $announcement->title;
        $announcement->delete();

        $this->audit()->record('content.deleted', null, 'Deleted announcement “'.$title.'”');

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:300'],
            'link_label' => ['nullable', 'string', 'max:60'],
            'link_url' => ['nullable', 'string', 'max:500', new SafeUrl],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
