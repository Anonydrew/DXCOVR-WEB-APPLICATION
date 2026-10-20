<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records administrative actions. Failures are logged but never interrupt the action itself.
 */
class AuditLogger
{
    /**
     * Attributes that must never be written to the audit trail.
     */
    protected const REDACTED = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'cv_path', 'token'];

    public function __construct(protected Request $request) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(string $action, ?Model $subject = null, ?string $description = null, array $properties = []): ?AuditLog
    {
        try {
            return AuditLog::query()->create([
                'user_id' => Auth::id(),
                'action' => $action,
                'auditable_type' => $subject?->getMorphClass(),
                'auditable_id' => $subject?->getKey(),
                'description' => $description ? Str::limit($description, 250) : null,
                'properties' => $properties === [] ? null : $this->redact($properties),
                'ip_address' => $this->request->ip(),
                'user_agent' => Str::limit((string) $this->request->userAgent(), 490),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to write audit log entry.', ['action' => $action, 'error' => $exception->getMessage()]);

            return null;
        }
    }

    /**
     * Summarise changed attributes for an update without storing large bodies.
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public function changes(Model $model): array
    {
        $changes = [];

        foreach ($model->getChanges() as $attribute => $value) {
            if (in_array($attribute, ['updated_at', 'updated_by'], true)) {
                continue;
            }

            $changes[$attribute] = [
                'from' => $this->summarise($model->getOriginal($attribute)),
                'to' => $this->summarise($value),
            ];
        }

        return $changes;
    }

    protected function summarise(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        return is_string($value) ? Str::limit(strip_tags($value), 120) : $value;
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    protected function redact(array $properties): array
    {
        foreach ($properties as $key => $value) {
            if (in_array($key, self::REDACTED, true)) {
                $properties[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $properties[$key] = $this->redact($value);
            }
        }

        return $properties;
    }
}
