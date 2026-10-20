<?php

namespace App\Services;

use App\Enums\MediaKind;
use App\Exceptions\MediaInUseException;
use App\Models\Media;
use App\Models\User;
use App\Support\Media\SvgSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

/**
 * Stores public website media through the configured filesystem disk.
 *
 * - File types are determined from file contents, never the client filename.
 * - Raster images are decoded and re-encoded, which strips EXIF metadata
 *   (including GPS location) and anything appended to the image payload.
 * - Files are stored under random names; original names are kept as metadata only.
 */
class MediaLibrary
{
    /**
     * Image formats that are safely re-encoded and get responsive renditions.
     */
    protected const REENCODE = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(protected MediaUsage $usage, protected AuditLogger $audit) {}

    public function disk(): string
    {
        return (string) config('dxcovr.media.disk', 'public');
    }

    /**
     * @param  array{title?: string|null, alt?: string|null, caption?: string|null, folder?: string|null}  $attributes
     */
    public function store(UploadedFile $file, array $attributes = [], ?User $uploader = null): Media
    {
        [$kind, $extension] = $this->classify($file);

        $media = new Media;
        $media->fill(array_intersect_key($attributes, array_flip(['title', 'alt', 'caption', 'folder'])));
        $media->disk = $this->disk();
        $media->uploaded_by = $uploader?->getKey();

        $this->writeFile($media, $file, $kind, $extension);

        $media->save();

        $this->audit->record('media.uploaded', $media, 'Uploaded '.$media->original_name);

        return $media;
    }

    /**
     * Replace the file behind an existing media record, keeping every reference to it intact.
     */
    public function replace(Media $media, UploadedFile $file): Media
    {
        [$kind, $extension] = $this->classify($file);

        if ($kind !== $media->kind) {
            throw ValidationException::withMessages([
                'file' => 'The replacement must be the same kind of file ('.$media->kind->label().').',
            ]);
        }

        $oldPaths = $media->storagePaths();
        $oldDisk = $media->disk;

        $media->disk = $this->disk();
        $this->writeFile($media, $file, $kind, $extension);
        $media->save();

        Storage::disk($oldDisk)->delete(array_diff($oldPaths, $media->storagePaths()));

        $this->audit->record('media.replaced', $media, 'Replaced file for '.$media->displayName());

        return $media;
    }

    /**
     * @throws MediaInUseException when the media is still referenced.
     */
    public function delete(Media $media): void
    {
        $usages = $this->usage->find($media);

        if ($usages !== []) {
            throw new MediaInUseException($usages);
        }

        DB::transaction(function () use ($media): void {
            $paths = $media->storagePaths();
            $disk = $media->disk;
            $name = $media->original_name;

            $media->delete();
            Storage::disk($disk)->delete($paths);

            $this->audit->record('media.deleted', null, 'Deleted media '.$name);
        });
    }

    /**
     * Determine the media kind and canonical extension from the file contents.
     *
     * @return array{0: MediaKind, 1: string}
     */
    public function classify(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->guessExtension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;

        foreach (config('dxcovr.media.types') as $kind => $rules) {
            if (in_array($extension, $rules['extensions'], true)) {
                return [MediaKind::from($kind), $extension];
            }
        }

        throw ValidationException::withMessages(['file' => 'This file type is not permitted.']);
    }

    protected function writeFile(Media $media, UploadedFile $file, MediaKind $kind, string $extension): void
    {
        $directory = 'media/'.now()->format('Y/m');
        $basename = Str::lower((string) Str::ulid());
        $path = $directory.'/'.$basename.'.'.$extension;
        $disk = Storage::disk($media->disk);

        $media->width = null;
        $media->height = null;
        $media->variants = null;

        if ($kind === MediaKind::Image && $extension === 'svg') {
            try {
                $clean = SvgSanitizer::sanitize((string) file_get_contents($file->getRealPath()));
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages(['file' => $exception->getMessage()]);
            }

            $disk->put($path, $clean['svg'], ['visibility' => 'public']);
            $media->width = $clean['width'];
            $media->height = $clean['height'];
        } elseif ($kind === MediaKind::Image) {
            $this->guardPixelCount($file);
            $this->ensureProcessingMemory();

            try {
                $image = Image::fromUpload($file);
                [$width, $height] = $image->dimensions();
            } catch (Throwable) {
                throw ValidationException::withMessages(['file' => 'The image could not be read. It may be corrupt.']);
            }

            if (in_array($extension, self::REENCODE, true)) {
                $maxDimension = (int) config('dxcovr.media.max_dimension', 3200);
                $image = $image->orient();

                if (max($width, $height) > $maxDimension) {
                    $image = $width >= $height ? $image->scale(width: $maxDimension) : $image->scale(height: $maxDimension);
                }

                $bytes = $image->toFormat($extension)->quality(92)->toBytes();
                unset($image);
                $disk->put($path, $bytes, ['visibility' => 'public']);
                [$width, $height] = getimagesizefromstring($bytes) ?: [$width, $height];
                unset($bytes);
            } else {
                $disk->putFileAs($directory, $file, $basename.'.'.$extension, ['visibility' => 'public']);
            }

            $media->width = $width;
            $media->height = $height;
        } else {
            $disk->putFileAs($directory, $file, $basename.'.'.$extension, ['visibility' => 'public']);
        }

        $media->path = $path;
        $media->kind = $kind;
        $media->extension = $extension;
        $media->mime_type = (string) $file->getMimeType();
        $media->original_name = Str::limit($this->safeOriginalName($file), 250, '');
        $media->size = (int) $disk->size($path);

        if ($kind === MediaKind::Image && in_array($extension, self::REENCODE, true)) {
            $media->variants = $this->generateVariants($media, $directory, $basename);
        }
    }

    /**
     * Rejects images whose decoded size would exhaust memory, reading only the
     * header (no decode). 60 megapixels covers any camera or phone photo.
     */
    protected function guardPixelCount(UploadedFile $file): void
    {
        $size = @getimagesize($file->getRealPath());
        $maxPixels = (int) config('dxcovr.media.max_pixels', 60_000_000);

        if (is_array($size) && $maxPixels < $size[0] * $size[1]) {
            throw ValidationException::withMessages([
                'file' => 'This image is '.$size[0].' × '.$size[1].' pixels, which is too large to process. Resize it to under 6000 pixels wide and try again.',
            ]);
        }
    }

    /**
     * Decoding a large photo needs far more memory than its file size, so the
     * limit is raised for image processing only (never lowered).
     */
    protected function ensureProcessingMemory(): void
    {
        $target = (string) config('dxcovr.media.processing_memory', '512M');
        $current = (string) ini_get('memory_limit');

        if ($current !== '-1' && $this->bytes($current) < $this->bytes($target)) {
            @ini_set('memory_limit', $target);
        }
    }

    protected function bytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * Rebuilds an image's resized renditions from its stored original, for
     * example after the rendition widths or quality settings change.
     */
    public function refreshVariants(Media $media): void
    {
        if ($media->kind !== MediaKind::Image || ! in_array($media->extension, self::REENCODE, true)) {
            return;
        }

        $this->ensureProcessingMemory();
        $oldPaths = array_values($media->variants ?? []);
        $directory = dirname($media->path);
        $basename = pathinfo($media->path, PATHINFO_FILENAME);

        $media->variants = $this->generateVariants($media, $directory, $basename);
        $media->save();

        Storage::disk($media->disk)->delete(array_diff($oldPaths, array_values($media->variants ?? [])));
    }

    /**
     * @return array<int, string>|null
     */
    protected function generateVariants(Media $media, string $directory, string $basename): ?array
    {
        $variants = [];
        $disk = Storage::disk($media->disk);

        foreach (config('dxcovr.media.variant_widths', []) as $width) {
            if ($media->width !== null && $width >= $media->width) {
                continue;
            }

            try {
                $variantPath = $directory.'/'.$basename.'-'.$width.'.webp';
                $bytes = Image::fromBytes((string) $disk->get($media->path))->scale(width: $width)->toWebp()->quality(85)->toBytes();
                $disk->put($variantPath, $bytes, ['visibility' => 'public']);
                $variants[$width] = $variantPath;
            } catch (Throwable $exception) {
                Log::warning('Unable to generate image rendition.', ['media' => $media->path, 'width' => $width, 'error' => $exception->getMessage()]);
            }
        }

        return $variants === [] ? null : $variants;
    }

    protected function safeOriginalName(UploadedFile $file): string
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $clean = trim((string) preg_replace('/[^\pL\pN\s._-]+/u', '', $name));

        return ($clean !== '' ? $clean : 'file').'.'.strtolower((string) $file->guessExtension());
    }
}
