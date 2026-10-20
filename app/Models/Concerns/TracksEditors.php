<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Records which administrator created and last updated a record.
 */
trait TracksEditors
{
    public static function bootTracksEditors(): void
    {
        static::creating(function ($model): void {
            if (Auth::check() && $model->created_by === null) {
                $model->created_by = Auth::id();
            }
        });

        static::saving(function ($model): void {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
