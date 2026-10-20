<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(timestamps: false)]
#[Fillable(['user_id', 'from_stage', 'to_stage', 'note', 'applicant_notified', 'created_at'])]
class ApplicationStageChange extends Model
{
    protected function casts(): array
    {
        return [
            'from_stage' => ApplicationStage::class,
            'to_stage' => ApplicationStage::class,
            'applicant_notified' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
