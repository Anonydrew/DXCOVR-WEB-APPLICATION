<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Rules\RasterImage;
use Illuminate\Validation\Rule;

trait HasSeoRules
{
    /**
     * @return array<string, mixed>
     */
    protected function seoRules(): array
    {
        return [
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:160'],
            'seo.meta_description' => ['nullable', 'string', 'max:320'],
            'seo.canonical_url' => ['nullable', 'string', 'url:http,https', 'max:500'],
            'seo.og_title' => ['nullable', 'string', 'max:160'],
            'seo.og_description' => ['nullable', 'string', 'max:320'],
            'seo.og_image_id' => ['nullable', 'integer', new RasterImage],
            'seo.noindex' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Rule for a URL slug that is unique within the given table.
     *
     * @return list<mixed>
     */
    protected function slugRules(string $table, mixed $ignoreId = null): array
    {
        return [
            'nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::unique($table, 'slug')->ignore($ignoreId),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function seoMessages(): array
    {
        return [
            'slug.regex' => 'The slug may contain only lower-case letters, numbers and single hyphens.',
            'slug.unique' => 'This slug is already used. Choose another, or leave it blank to generate one.',
        ];
    }
}
