<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use App\Enums\EnquiryType;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type', 'name', 'organisation', 'email', 'phone', 'service_id', 'product_id', 'project_type', 'budget_range',
    'timeframe', 'message', 'consent_given_at', 'source_url', 'ip_address', 'user_agent',
])]
#[Hidden(['ip_address', 'user_agent'])]
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    public const BUDGET_RANGES = [
        'under_5k' => 'Under GHS 5,000',
        '5k_25k' => 'GHS 5,000 – 25,000',
        '25k_100k' => 'GHS 25,000 – 100,000',
        'over_100k' => 'Over GHS 100,000',
        'not_sure' => 'Not sure yet',
    ];

    public const TIMEFRAMES = [
        'asap' => 'As soon as possible',
        '1_3_months' => 'Within 1–3 months',
        '3_6_months' => 'Within 3–6 months',
        'flexible' => 'Flexible / exploring',
    ];

    public const PROJECT_TYPES = [
        'new_product' => 'New product or platform',
        'existing_system' => 'Improve an existing system',
        'website' => 'Website or web application',
        'immersive' => 'Immersive / 3D experience',
        'hardware' => 'Hardware or IoT prototype',
        'design' => 'Design or brand work',
        'support' => 'IT support or maintenance',
        'other' => 'Something else',
    ];

    protected function casts(): array
    {
        return [
            'type' => EnquiryType::class,
            'status' => EnquiryStatus::class,
            'consent_given_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $enquiry): void {
            $enquiry->reference ??= (string) str()->ulid();
            $enquiry->status ??= EnquiryStatus::New;
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at')->where('status', '!=', EnquiryStatus::Spam->value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('organisation', 'like', "%{$term}%")
            ->orWhere('message', 'like', "%{$term}%"));
    }

    public function budgetLabel(): ?string
    {
        return self::BUDGET_RANGES[$this->budget_range] ?? null;
    }

    public function timeframeLabel(): ?string
    {
        return self::TIMEFRAMES[$this->timeframe] ?? null;
    }

    public function projectTypeLabel(): ?string
    {
        return self::PROJECT_TYPES[$this->project_type] ?? $this->project_type;
    }
}
