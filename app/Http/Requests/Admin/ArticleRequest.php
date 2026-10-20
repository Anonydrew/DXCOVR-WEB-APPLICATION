<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
{
    use HasSeoRules;

    /**
     * Authorisation is enforced in the controller through ArticlePolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => ['required', 'string', 'max:200'],
            'slug' => $this->slugRules('articles', $this->route('article')?->id),
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:1000000'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'byline' => ['nullable', 'string', 'max:120'],
            'show_author' => ['boolean'],
            'show_toc' => ['boolean'],
            'is_featured' => ['boolean'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:article_categories,id'],
            'tags' => ['nullable', 'string', 'max:500'],
        ], $this->seoRules());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->seoMessages();
    }
}
