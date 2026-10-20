<?php

namespace App\Console\Commands;

use App\Enums\MediaKind;
use App\Models\Media;
use App\Services\MediaLibrary;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('media:refresh-variants {--id=* : Only refresh these media IDs}')]
#[Description('Rebuild resized image renditions from the stored originals. Safe to run in production.')]
class RefreshMediaVariants extends Command
{
    public function handle(MediaLibrary $library): int
    {
        $query = Media::query()->where('kind', MediaKind::Image->value)->whereIn('extension', ['jpg', 'jpeg', 'png', 'webp']);

        if ($ids = $this->option('id')) {
            $query->whereKey($ids);
        }

        $failed = 0;

        $this->withProgressBar($query->lazyById(50), function (Media $media) use ($library, &$failed): void {
            try {
                $library->refreshVariants($media);
            } catch (Throwable $exception) {
                $failed++;
                $this->newLine();
                $this->components->warn("Media #{$media->id} skipped: {$exception->getMessage()}");
            }
        });

        $this->newLine(2);
        $failed === 0
            ? $this->components->info('Image renditions refreshed.')
            : $this->components->warn("Finished with {$failed} skipped image(s).");

        return self::SUCCESS;
    }
}
