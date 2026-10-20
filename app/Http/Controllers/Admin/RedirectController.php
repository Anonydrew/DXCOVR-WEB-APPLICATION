<?php

namespace App\Http\Controllers\Admin;

use App\Models\Redirect;
use App\Rules\SafeUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RedirectController extends AdminController
{
    public function index(Request $request): View
    {
        return view('admin.redirects.index', [
            'redirects' => Redirect::query()
                ->when($request->query('q'), fn ($query, $term) => $query->where('from_path', 'like', '%'.addcslashes((string) $term, '%_').'%')->orWhere('to_url', 'like', '%'.addcslashes((string) $term, '%_').'%'))
                ->orderBy('from_path')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.redirects.form', ['redirect' => new Redirect(['status_code' => 301, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $redirect = Redirect::query()->create($this->validated($request));

        $this->audit()->record('content.redirect_created', $redirect, 'Created redirect '.$redirect->from_path.' → '.$redirect->to_url);

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect saved.');
    }

    public function edit(Redirect $redirect): View
    {
        return view('admin.redirects.form', ['redirect' => $redirect]);
    }

    public function update(Request $request, Redirect $redirect): RedirectResponse
    {
        $redirect->update($this->validated($request, $redirect));

        $this->audit()->record('content.redirect_updated', $redirect, 'Updated redirect '.$redirect->from_path.' → '.$redirect->to_url);

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect updated.');
    }

    public function destroy(Redirect $redirect): RedirectResponse
    {
        $from = $redirect->from_path;
        $redirect->delete();

        $this->audit()->record('content.redirect_deleted', null, 'Deleted redirect from '.$from);

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Redirect $redirect = null): array
    {
        $request->merge(['from_path' => Redirect::normalisePath((string) $request->input('from_path'))]);

        $data = $request->validate([
            'from_path' => [
                'required', 'string', 'max:255', 'regex:/^\/[^\s?#]*$/',
                'not_regex:/^\/(admin|build|storage|preview)(\/|$)/',
                Rule::unique('redirects', 'from_path')->ignore($redirect?->id),
            ],
            'to_url' => ['required', 'string', 'max:1000', new SafeUrl, 'different:from_path'],
            'status_code' => ['required', Rule::in([301, 302, 307, 308])],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'from_path.regex' => 'Enter a path starting with “/”, without a query string.',
            'from_path.not_regex' => 'Admin, preview and asset paths cannot be redirected.',
            'to_url.different' => 'A redirect cannot point to itself.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
