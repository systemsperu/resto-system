<?php

namespace App\Jobs;

use App\Models\SunatSubmission;
use App\Models\SystemAudit;
use App\Services\Sunat\SunatService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSunatCreditNote implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $submissionId)
    {
        $this->onQueue('sunat');
    }

    /**
     * Execute the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->submissionId;
    }

    public function handle(SunatService $service): void
    {
        $submission = SunatSubmission::findOrFail($this->submissionId);
        if ($submission->status === 'accepted') {
            return;
        }
        $submission->update(['status' => 'processing', 'attempts' => $submission->attempts + 1, 'last_attempt_at' => now()]);
        $note = $submission->creditNote()->firstOrFail();
        if ($note->sunat_status === 'ACCEPTED') {
            $submission->update(['status' => 'accepted', 'completed_at' => now()]);

            return;
        }
        $service->sendCreditNote($note);
        $note->refresh();
        $status = $note->sunat_status === 'ACCEPTED' ? 'accepted' : 'failed';
        $submission->update(['status' => $status, 'completed_at' => $status === 'accepted' ? now() : null]);
        SystemAudit::create(['action' => 'sunat.credit_note.send', 'target' => 'credit_note:'.$note->id, 'status' => $status, 'metadata' => ['submission_id' => $submission->id, 'sunat_code' => $note->sunat_code]]);
        if ($status === 'failed') {
            throw new \RuntimeException('La nota de crédito no fue aceptada.');
        }
    }
}
