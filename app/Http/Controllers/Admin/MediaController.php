<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaKind;
use App\Exceptions\MediaInUseException;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Http\Resources\MediaPickerResource;
use App\Models\Media;
use App\Services\MediaLibrary;
use App\Services\MediaUsage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaController extends AdminController
{
    public function __construct(protected MediaLibrary $library) {}

    public function index(Request $request): View
    {
        return view('admin.media.index', [
            'items' => $this->filtered($request)->latest()->paginate(40)->withQueryString(),
            'folders' => Media::query()->whereNotNull('folder')->distinct()->orderBy('folder')->pluck('folder'),
            'limits' => config('dxcovr.media.types'),
        ]);
    }

    /**
     * JSON listing for the media picker dialog.
     */
    public function picker(Request $request): JsonResponse
    {
        $page = $this->filtered($request)->latest()->paginate(30);

        return response()->json([
            'data' => MediaPickerResource::collection($page->getCollection())->resolve(),
            'next_page_url' => $page->nextPageUrl(),
        ]);
    }

    public function store(MediaUploadRequest $request): JsonResponse|RedirectResponse
    {
        $media = $this->library->store($request->file('file'), $request->safe()->only(['alt', 'title', 'folder']), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['media' => MediaPickerResource::make($media)->resolve()], 201);
        }

        return redirect()->route('admin.media.show', $media)->with('status', 'Uploaded. Add alternative text so the image is accessible.');
    }

    public function show(Media $media, MediaUsage $usage): View
    {
        $media->load('uploader');

        return view('admin.media.show', [
            'media' => $media,
            'usages' => $usage->find($media),
        ]);
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $media->fill($request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:300'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'folder' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\pN\s_-]+$/u'],
        ]))->save();

        $this->audit()->record('media.updated', $media, 'Updated details for '.$media->displayName());

        return back()->with('status', 'Media details saved.');
    }

    public function replace(MediaUploadRequest $request, Media $media): RedirectResponse
    {
        $this->library->replace($media, $request->file('file'));

        return back()->with('status', 'File replaced. Everywhere this media is used now shows the new file.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        try {
            $this->library->delete($media);
        } catch (MediaInUseException $exception) {
            return back()->with('error', 'This file is still in use ('.count($exception->usages).' place(s)). Remove it from those pages first.');
        }

        return redirect()->route('admin.media.index')->with('status', 'Media deleted.');
    }

    protected function filtered(Request $request): Builder
    {
        return Media::query()
            ->when(in_array($request->query('kind'), MediaKind::values(), true), fn (Builder $query) => $query->where('kind', $request->query('kind')))
            ->when($request->query('folder'), fn (Builder $query, $folder) => $query->where('folder', $folder))
            ->when($request->boolean('missing_alt'), fn (Builder $query) => $query->images()->where(fn (Builder $query) => $query->whereNull('alt')->orWhere('alt', '')))
            ->search($request->string('q')->limit(100, '')->toString() ?: null);
    }
}
