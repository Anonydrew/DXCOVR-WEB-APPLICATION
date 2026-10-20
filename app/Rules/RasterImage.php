<?php

namespace App\Rules;

use App\Enums\MediaKind;
use App\Models\Media;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A media library image that other platforms can display: social networks
 * and search engines do not accept SVG for previews or organisation logos.
 */
class RasterImage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $media = Media::query()->find((int) $value);

        if (! $media || $media->kind !== MediaKind::Image) {
            $fail('Choose an image from the media library.');

            return;
        }

        if ($media->extension === 'svg') {
            $fail('Social networks and search engines do not support SVG here. Choose a JPG, PNG or WebP image.');
        }
    }
}
