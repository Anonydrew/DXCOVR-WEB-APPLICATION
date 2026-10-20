<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoMeta;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Shared helpers for admin controllers.
 */
abstract class AdminController extends Controller
{
    protected function audit(): AuditLogger
    {
        return app(AuditLogger::class);
    }

    /**
     * Persist SEO overrides and return a warning when they duplicate another page's metadata.
     *
     * @param  array<string, mixed>|null  $input
     */
    protected function saveSeo(Model $model, ?array $input): ?string
    {
        if ($input === null || ! method_exists($model, 'saveSeo')) {
            return null;
        }

        $seo = $model->saveSeo([
            'meta_title' => $input['meta_title'] ?? null,
            'meta_description' => $input['meta_description'] ?? null,
            'canonical_url' => $input['canonical_url'] ?? null,
            'og_title' => $input['og_title'] ?? null,
            'og_description' => $input['og_description'] ?? null,
            'og_image_id' => $input['og_image_id'] ?? null,
            'noindex' => (bool) ($input['noindex'] ?? false),
        ]);

        $duplicates = [];

        foreach (['meta_title' => 'meta title', 'meta_description' => 'meta description'] as $column => $label) {
            if (filled($seo->{$column}) && SeoMeta::query()->whereKeyNot($seo->id)->where($column, $seo->{$column})->exists()) {
                $duplicates[] = $label;
            }
        }

        return $duplicates === []
            ? null
            : 'Another page already uses the same '.implode(' and ', $duplicates).'. Unique metadata helps search engines tell pages apart.';
    }

    /**
     * Redirect back to the edit screen with a status message (and an optional SEO warning).
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function saved(string $route, array $parameters, string $message, ?string $warning = null): RedirectResponse
    {
        $redirect = redirect()->route($route, $parameters)->with('status', $message);

        return $warning ? $redirect->with('warning', $warning) : $redirect;
    }
}
