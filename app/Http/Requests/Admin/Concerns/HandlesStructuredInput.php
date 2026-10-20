<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Rules\SafeUrl;

/**
 * Cleans and validates repeater rows and simple string lists submitted by the admin forms.
 */
trait HandlesStructuredInput
{
    /**
     * Drop blank rows and trim values so empty repeater rows never fail validation.
     *
     * @param  list<string>  $fields
     */
    protected function cleanRows(string $key, array $fields): void
    {
        $rows = $this->input($key);

        if (! is_array($rows)) {
            $this->merge([$key => []]);

            return;
        }

        $clean = collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => collect($fields)->mapWithKeys(fn (string $field) => [$field => is_string($row[$field] ?? null) ? trim($row[$field]) : ($row[$field] ?? null)])->all())
            ->filter(fn (array $row) => collect($row)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
            ->values()
            ->all();

        $this->merge([$key => $clean]);
    }

    protected function cleanList(string $key): void
    {
        $items = $this->input($key);

        $this->merge([$key => collect(is_array($items) ? $items : [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn (string $item) => trim($item))
            ->unique()
            ->values()
            ->all()]);
    }

    /**
     * Rules for a list of {title, body} items.
     *
     * @return array<string, mixed>
     */
    protected function titleBodyRules(string $key, int $max = 12): array
    {
        return [
            $key => ['nullable', 'array', 'max:'.$max],
            $key.'.*.title' => ['required', 'string', 'max:160'],
            $key.'.*.body' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function stringListRules(string $key, int $max = 30, int $length = 160): array
    {
        return [
            $key => ['nullable', 'array', 'max:'.$max],
            $key.'.*' => ['string', 'max:'.$length],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function linkRules(string $key, int $max = 12): array
    {
        return [
            $key => ['nullable', 'array', 'max:'.$max],
            $key.'.*.label' => ['required', 'string', 'max:80'],
            $key.'.*.url' => ['required', 'string', 'max:500', new SafeUrl(false)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function galleryRules(string $key, int $max = 40): array
    {
        return [
            $key => ['nullable', 'array', 'max:'.$max],
            $key.'.*.media_id' => ['required', 'integer', 'exists:media,id'],
            $key.'.*.caption' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function structuredMessages(): array
    {
        return [
            '*.*.title.required' => 'Each item needs a title.',
            '*.*.label.required' => 'Each link needs a label.',
            '*.*.url.required' => 'Each link needs an address.',
        ];
    }
}
