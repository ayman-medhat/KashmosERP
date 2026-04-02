<?php

namespace App\Core\Observers;

use App\Core\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditableObserver
{
    public function __construct(
        protected AuditLogger $auditLogger,
    ) {
    }

    public function created(Model $model): void
    {
        $this->auditLogger->log('created', $model, [], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        $original = collect(array_keys($changes))
            ->mapWithKeys(fn (string $key): array => [$key => $model->getOriginal($key)])
            ->all();

        $this->auditLogger->log('updated', $model, $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->auditLogger->log('deleted', $model, $model->getOriginal(), []);
    }
}
