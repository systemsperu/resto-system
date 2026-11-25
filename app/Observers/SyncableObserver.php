<?php

namespace App\Observers;

use App\Services\Sync\SyncPublisher;
use Illuminate\Database\Eloquent\Model;

class SyncableObserver
{
    public function __construct(private readonly SyncPublisher $publisher) {}

    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    private function record(Model $model, string $action): void
    {
        if (config('sync.mode') !== 'local_sync') {
            return;
        }
        $type = class_basename($model);
        $version = (int) (($model->updated_at?->format('Uu')) ?: hrtime(true));
        $this->publisher->publish($type, $model->getKey(), strtolower($type).'.'.$action, $action === 'deleted' ? ['id' => $model->getKey()] : $model->toArray(), $version);
    }
}
