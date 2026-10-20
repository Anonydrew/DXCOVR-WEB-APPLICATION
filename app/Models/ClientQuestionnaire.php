<?php

namespace App\Models;

use App\Enums\QuestionnaireStatus;
use App\Models\Concerns\TracksEditors;
use App\Support\Onboarding\QuestionnaireForm;
use Database\Factories\ClientQuestionnaireFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A private onboarding questionnaire shared with one client through an
 * unguessable link. Only a SHA-256 hash of the link token is used for
 * look-ups; the token itself is stored encrypted so staff can copy the
 * link again.
 */
#[Fillable(['client_name', 'client_email', 'client_company', 'project_name', 'intro_message', 'sections', 'enquiry_id', 'expires_at', 'internal_notes'])]
#[Hidden(['token', 'token_hash'])]
class ClientQuestionnaire extends Model
{
    /** @use HasFactory<ClientQuestionnaireFactory> */
    use HasFactory, TracksEditors;

    public const TOKEN_LENGTH = 48;

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'answers' => 'array',
            'status' => QuestionnaireStatus::class,
            'token' => 'encrypted',
            'expires_at' => 'datetime',
            'first_opened_at' => 'datetime',
            'last_saved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $questionnaire): void {
            $questionnaire->reference ??= (string) Str::ulid();
            $questionnaire->status ??= QuestionnaireStatus::Pending;

            if ($questionnaire->token_hash === null) {
                $questionnaire->issueToken();
            }
        });

        static::deleting(function (self $questionnaire): void {
            $questionnaire->files()->get()->each->delete();
        });
    }

    /**
     * Generates a new link token, invalidating any previous link. Returns the plain token.
     */
    public function issueToken(): string
    {
        $token = Str::random(self::TOKEN_LENGTH);
        $this->token = $token;
        $this->token_hash = self::hashToken($token);

        return $token;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        if (strlen($token) !== self::TOKEN_LENGTH) {
            return null;
        }

        return static::query()->where('token_hash', self::hashToken($token))->first();
    }

    public function shareUrl(): string
    {
        return route('onboarding.show', $this->token);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ClientQuestionnaireFile::class)->oldest('id');
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->where('status', QuestionnaireStatus::Submitted->value)->whereNull('reviewed_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            foreach (['client_name', 'client_email', 'client_company', 'project_name'] as $column) {
                $query->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isSubmitted(): bool
    {
        return $this->status === QuestionnaireStatus::Submitted;
    }

    public function acceptsAnswers(): bool
    {
        return ! $this->isSubmitted() && ! $this->isExpired();
    }

    /**
     * @return array<string, array{title: string, intro: string, fields: array<string, array<string, mixed>>}>
     */
    public function formSections(): array
    {
        return QuestionnaireForm::enabled($this->sections);
    }

    public function answer(string $key, mixed $default = null): mixed
    {
        return data_get($this->answers ?? [], $key, $default);
    }

    /**
     * @return array{answered: int, total: int}
     */
    public function progress(): array
    {
        $sections = array_keys($this->formSections());
        $fileCount = $this->relationLoaded('files') ? $this->files->count() : $this->files()->count();
        $answered = collect($sections)->filter(fn (string $key) => QuestionnaireForm::sectionAnswered($key, $this->answers ?? [], $fileCount))->count();

        return ['answered' => $answered, 'total' => count($sections)];
    }

    /**
     * The name to greet the client with: the contact's own answer, else the admin-entered name.
     */
    public function contactName(): string
    {
        return (string) ($this->answer('about.contact_name') ?: $this->client_name);
    }
}
