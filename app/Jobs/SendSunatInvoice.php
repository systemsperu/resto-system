<?php

namespace App\Jobs;

use App\Models\SunatSubmission;
use App\Models\SystemAudit;
use App\Services\Sunat\SunatService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendSunatInvoice implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $submissionId)
    {
        $this->tries = max(1, (int) config('sunat.max_attempts', 3));
        $this->onQueue('sunat');
    }

    public function uniqueId(): string
    {
        return (string) $this->submissionId;
    }

    public function handle(SunatService $service): void
    {
        $submission = DB::transaction(function (): ?SunatSubmission {
            $record = SunatSubmission::query()->lockForUpdate()->find($this->submissionId);
            if (! $record || in_array($record->status, ['processing', 'accepted'], true)) {
                return null;
            }
            $record->update(['status' => 'processing', 'attempts' => $record->attempts + 1, 'last_attempt_at' => now()]);

            return $record;
        });

        if (! $submission) {
            return;
        }

        $order = $submission->order()->with('details.product')->firstOrFail();
        if ($order->sunat_status === 'ACCEPTED') {
            $submission->update(['status' => 'accepted', 'completed_at' => now()]);

            return;
        }

        $service->sendInvoice($order);
        $order->refresh();
        $status = $order->sunat_status === 'ACCEPTED' ? 'accepted' : 'failed';
        $submission->update(['status' => $status, 'completed_at' => $status === 'accepted' ? now() : null, 'last_error' => $status === 'failed' ? 'SUNAT no aceptó el comprobante.' : null]);
        SystemAudit::create(['action' => 'sunat.send', 'target' => 'order:'.$order->id, 'status' => $status, 'metadata' => ['submission_id' => $submission->id, 'sunat_code' => $order->sunat_code, 'attempt' => $submission->attempts]]);

        if ($status === 'failed') {
            throw new \RuntimeException('El intento SUNAT no fue aceptado.');
        }
    }

    public function failed(Throwable $exception): void
    {
        SunatSubmission::whereKey($this->submissionId)->where('status', '!=', 'accepted')->update(['status' => 'failed', 'last_error' => 'El envío falló; revise el registro restringido.']);
    }
}
