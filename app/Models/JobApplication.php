<?php

namespace App\Models;

use App\Enums\ApplicationStage;
use Database\Factories\JobApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;

/**
 * A candidate's application. CVs are stored on a private disk and are only
 * served through the authorised admin download route.
 */
#[Fillable([
    'job_listing_id', 'name', 'email', 'phone', 'cover_letter', 'portfolio_url', 'cv_disk', 'cv_path',
    'cv_original_name', 'cv_mime', 'cv_size', 'consent_given_at', 'ip_address', 'user_agent',
])]
#[Hidden(['cv_disk', 'cv_path', 'ip_address', 'user_agent'])]
class JobApplication extends Model
{
    /** @use HasFactory<JobApplicationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'stage' => ApplicationStage::class,
            'consent_given_at' => 'datetime',
            'stage_changed_at' => 'datetime',
            'decided_at' => 'datetime',
            'cv_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            $application->reference ??= (string) str()->ulid();
            $application->stage ??= ApplicationStage::Received;
        });
    }

    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ApplicationNote::class)->latest();
    }

    public function stageChanges(): HasMany
    {
        return $this->hasMany(ApplicationStageChange::class)->latest('created_at')->latest('id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('reference', $term));
    }

    /**
     * Applications whose retention period has elapsed: measured from the final
     * decision, or from submission when no decision was ever recorded.
     */
    public function scopeBeyondRetention(Builder $query, int $days): Builder
    {
        $cutoff = now()->subDays($days);

        return $query->where(fn (Builder $query) => $query
            ->where(fn (Builder $query) => $query->whereNotNull('decided_at')->where('decided_at', '<=', $cutoff))
            ->orWhere(fn (Builder $query) => $query->whereNull('decided_at')->where('created_at', '<=', $cutoff)));
    }

    public function cvHumanSize(): string
    {
        return Number::fileSize($this->cv_size, precision: 1);
    }
}
