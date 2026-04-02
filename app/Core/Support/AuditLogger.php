<?php

namespace App\Core\Support;

use App\Core\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLogger
{
    public function log(string $event, Model $model, array $oldValues = [], array $newValues = []): void
    {
        AuditLog::query()->create([
            'user_id' => auth()->id(),
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'event' => $event,
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    protected function sanitize(array $payload): array
    {
        return Arr::except($payload, [
            'password',
            'remember_token',
        ]);
    }
}
