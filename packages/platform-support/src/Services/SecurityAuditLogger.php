<?php

namespace Platform\PlatformSupport\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Platform\PlatformSupport\Models\AuditLog;

class SecurityAuditLogger
{
    public function log(
        string $action,
        ?string $actorType = null,
        ?int $actorId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $payload = [],
    ): void {
        try {
            if (! Schema::hasTable('audit_logs')) {
                return;
            }

            AuditLog::query()->create([
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'payload_json' => $this->safePayload($payload),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function logForActor(string $action, ?Model $actor = null, array $payload = []): void
    {
        $this->log(
            $action,
            $actor ? $actor::class : null,
            $actor ? (int) $actor->getKey() : null,
            null,
            null,
            $payload,
        );
    }

    public function logForSubject(string $action, Model $subject, ?Model $actor = null, array $payload = []): void
    {
        $this->log(
            $action,
            $actor ? $actor::class : null,
            $actor ? (int) $actor->getKey() : null,
            $subject::class,
            (int) $subject->getKey(),
            $payload,
        );
    }

    protected function safePayload(array $payload): array
    {
        return collect($payload)
            ->except([
                'password',
                'password_confirmation',
                'current_password',
                'new_password',
                'new_password_confirmation',
                'store_passwd',
                'api_password',
                'api_key',
                'api_secret',
                'webhook_secret',
                'Authorization',
            ])
            ->all();
    }
}
