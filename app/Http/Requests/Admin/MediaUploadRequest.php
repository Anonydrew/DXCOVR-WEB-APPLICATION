<?php

namespace App\Http\Requests\Admin;

use App\Rules\ReadableUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates public media uploads. The file type is determined from the file
 * contents (Laravel's "mimes" rule uses content sniffing), executable and
 * script formats including SVG are never accepted, and each media kind has
 * its own size limit.
 */
class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $types = config('dxcovr.media.types');
        $extensions = collect($types)->pluck('extensions')->flatten()->unique()->implode(',');
        $maxKb = collect($types)->max('max_kb');

        return [
            'file' => ['bail', 'required', 'file', new ReadableUpload, 'mimes:'.$extensions, 'max:'.$maxKb],
            'alt' => ['nullable', 'string', 'max:300'],
            'title' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:80', 'regex:/^[\pL\pN\s_-]+$/u'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $file = $this->file('file');

                if (! $file || $validator->errors()->has('file')) {
                    return;
                }

                $extension = strtolower((string) $file->guessExtension());
                $extension = $extension === 'jpeg' ? 'jpg' : $extension;

                foreach (config('dxcovr.media.types') as $kind => $rules) {
                    if (in_array($extension, $rules['extensions'], true) && $file->getSize() > $rules['max_kb'] * 1024) {
                        $validator->errors()->add('file', ucfirst($kind).' files must be '.round($rules['max_kb'] / 1024).' MB or smaller.');
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Upload a JPG, PNG, WebP, GIF, AVIF or SVG image, an MP4 or WebM video, or a PDF.',
            'file.max' => 'The file is too large.',
            'file.uploaded' => 'The upload failed. The file may be larger than the server allows.',
        ];
    }
}
