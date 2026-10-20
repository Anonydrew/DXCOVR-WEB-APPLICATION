<?php

namespace App\Models;

use App\Enums\ApplicationMethod;
use App\Enums\EmploymentType;
use App\Enums\WorkArrangement;
use App\Models\Concerns\HasPublication;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksEditors;
use Database\Factories\JobListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'slug', 'department_id', 'location', 'work_arrangement', 'employment_type', 'employment_type_label',
    'summary', 'description', 'responsibilities', 'requirements', 'nice_to_have', 'benefits', 'salary_range',
    'application_deadline', 'auto_close', 'application_method', 'application_email', 'application_url',
    'application_instructions',
])]
class JobListing extends Model
{
    /** @use HasFactory<JobListingFactory> */
    use HasFactory, HasPublication, HasSeo, HasSlug, TracksEditors;

    protected function casts(): array
    {
        return [
            'work_arrangement' => WorkArrangement::class,
            'employment_type' => EmploymentType::class,
            'application_method' => ApplicationMethod::class,
            'application_deadline' => 'datetime',
            'closed_at' => 'datetime',
            'auto_close' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Published listings that are still open for applications.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->published()
            ->whereNull('closed_at')
            ->where(fn (Builder $query) => $query
                ->whereNull('application_deadline')
                ->orWhere('application_deadline', '>', now()));
    }

    /**
     * Open listings whose deadline has passed and that are configured to close automatically.
     */
    public function scopeDueForClosing(Builder $query): Builder
    {
        return $query->whereNull('closed_at')
            ->where('auto_close', true)
            ->whereNotNull('application_deadline')
            ->where('application_deadline', '<=', now());
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('title', 'like', "%{$term}%")
            ->orWhere('summary', 'like', "%{$term}%")
            ->orWhere('location', 'like', "%{$term}%"));
    }

    public function isAcceptingApplications(): bool
    {
        return $this->isPubliclyVisible()
            && $this->closed_at === null
            && ($this->application_deadline === null || $this->application_deadline->isFuture());
    }

    public function acceptsFormApplications(): bool
    {
        return $this->isAcceptingApplications() && $this->application_method === ApplicationMethod::Form;
    }

    public function employmentTypeLabel(): string
    {
        if ($this->employment_type === EmploymentType::Other && filled($this->employment_type_label)) {
            return $this->employment_type_label;
        }

        return $this->employment_type?->label() ?? '';
    }

    public function publicUrl(): string
    {
        return route('careers.show', $this);
    }

    public function missingForPublication(): array
    {
        $missing = [];

        if (blank($this->summary)) {
            $missing[] = 'A summary is required.';
        }

        if (blank(strip_tags((string) $this->description))) {
            $missing[] = 'A role description is required.';
        }

        if ($this->application_method === ApplicationMethod::Email && blank($this->application_email)) {
            $missing[] = 'An application email address is required for email applications.';
        }

        if ($this->application_method === ApplicationMethod::External && blank($this->application_url)) {
            $missing[] = 'An application URL is required for external applications.';
        }

        if ($this->application_deadline !== null && $this->application_deadline->isPast()) {
            $missing[] = 'The application deadline is in the past.';
        }

        return $missing;
    }
}
