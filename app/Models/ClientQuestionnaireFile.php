<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * A file a client uploaded with their questionnaire. Stored on a private
 * disk and only ever served through authorised routes.
 */
#[Fillable(['disk', 'path', 'original_name', 'mime_type', 'size'])]
class ClientQuestionnaireFile extends Model
{
    protected static function booted(): void
    {
        static::deleted(function (self $file): void {
            Storage::disk($file->disk)->delete($file->path);
        });
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(ClientQuestionnaire::class, 'client_questionnaire_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        return Number::fileSize($this->size, precision: 1);
    }
}
