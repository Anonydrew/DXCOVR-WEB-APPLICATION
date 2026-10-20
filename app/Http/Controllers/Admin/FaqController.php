<?php

namespace App\Http\Controllers\Admin;

use App\Models\Faq;
use App\Models\Product;
use App\Models\Service;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FaqController extends AdminController
{
    public function __construct(protected HtmlSanitizer $sanitizer) {}

    public function index(Request $request): View
    {
        $faqs = Faq::query()
            ->with(['service', 'product'])
            ->when($request->query('group'), fn (Builder $query, $group) => $query->where('group', $group))
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'canManage' => $request->user()->can('company.manage'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.faqs.form', $this->formData(new Faq([
            'group' => $request->query('group', 'general'),
            'is_published' => true,
            'service_id' => $request->query('service'),
            'product_id' => $request->query('product'),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $faq = new Faq;
        $faq->sort_order = (int) Faq::query()->max('sort_order') + 1;
        $faq->fill($this->validated($request))->save();

        $this->audit()->record('content.created', $faq, 'Added FAQ “'.$faq->question.'”');

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ saved.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.form', $this->formData($faq));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->fill($this->validated($request))->save();

        $this->audit()->record('content.updated', $faq, 'Updated FAQ “'.$faq->question.'”');

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ updated.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $question = $faq->question;
        $faq->delete();

        $this->audit()->record('content.deleted', null, 'Deleted FAQ “'.$question.'”');

        return redirect()->route('admin.faqs.index')->with('status', 'FAQ deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:20000'],
            'group' => ['required', Rule::in(array_keys(Faq::GROUPS))],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'is_published' => ['boolean'],
        ]);

        $data['answer'] = $this->sanitizer->basic($data['answer']) ?? '';
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Faq $faq): array
    {
        return [
            'faq' => $faq,
            'services' => Service::query()->ordered()->pluck('title', 'id'),
            'products' => Product::query()->ordered()->pluck('name', 'id'),
        ];
    }
}
