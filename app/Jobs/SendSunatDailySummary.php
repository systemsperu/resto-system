<?php

namespace App\Jobs;

use App\Models\SunatSubmission;
use App\Models\SystemAudit;
use App\Services\Sunat\DailySummaryBuilder;
use App\Services\Sunat\SunatConfig;
use App\Services\Sunat\SunatService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSunatDailySummary implements ShouldBeUnique, ShouldQueue
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
        $summary = $submission->dailySummary()->firstOrFail();
        $document = (new DailySummaryBuilder(new SunatConfig))->rebuild($summary);
        $service->sendSummary($summary, $document);
        $summary->refresh();
        $status = in_array($summary->sunat_status, ['TICKET', 'ACCEPTED'], true) ? 'accepted' : 'failed';
        $submission->update(['status' => $status, 'completed_at' => $status === 'accepted' ? now() : null]);
        SystemAudit::create(['action' => 'sunat.summary.send', 'target' => 'daily_summary:'.$summary->id, 'status' => $status, 'metadata' => ['submission_id' => $submission->id, 'ticket_assigned' => filled($summary->ticket)]]);
        if ($status === 'failed') {
            throw new \RuntimeException('El resumen diario no obtuvo ticket.');
        }
    }
}
