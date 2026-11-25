<?php

namespace App\Models;

use App\Jobs\SendSunatCreditNote;
use App\Jobs\SendSunatDailySummary;
use App\Jobs\SendSunatInvoice;
use Illuminate\Database\Eloquent\Model;

class SunatSubmission extends Model
{
    protected $fillable = ['order_id', 'credit_note_id', 'daily_summary_id', 'document_kind', 'idempotency_key', 'status', 'attempts', 'last_error', 'last_attempt_at', 'completed_at'];

    protected $casts = ['last_attempt_at' => 'datetime', 'completed_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function dailySummary()
    {
        return $this->belongsTo(DailySummary::class);
    }

    public static function schedule(Order $order): self
    {
        if (! $order->isElectronic() || ! $order->serie || ! $order->correlativo) {
            throw new \RuntimeException('El comprobante no tiene identificación fiscal completa.');
        }

        $key = implode('-', [$order->document_type, $order->serie, $order->correlativo]);
        $submission = self::firstOrCreate(
            ['idempotency_key' => $key],
            ['order_id' => $order->id, 'document_kind' => 'invoice', 'status' => 'pending']
        );

        if (config('sunat.send_enabled', false) && ! in_array($submission->status, ['queued', 'processing', 'accepted'], true)) {
            $submission->update(['status' => 'queued', 'last_error' => null]);
            SendSunatInvoice::dispatch($submission->id)->afterCommit();
        }

        return $submission->refresh();
    }

    public static function scheduleCreditNote(CreditNote $note): self
    {
        $key = 'NotaCredito-'.$note->full_number;
        $submission = self::firstOrCreate(['idempotency_key' => $key], ['credit_note_id' => $note->id, 'document_kind' => 'credit_note', 'status' => 'pending']);
        if (config('sunat.send_enabled', false) && ! in_array($submission->status, ['queued', 'processing', 'accepted'], true)) {
            $submission->update(['status' => 'queued', 'last_error' => null]);
            SendSunatCreditNote::dispatch($submission->id)->afterCommit();
        }

        return $submission->refresh();
    }

    public static function scheduleSummary(DailySummary $summary): self
    {
        $key = 'Resumen-'.$summary->identifier;
        $submission = self::firstOrCreate(['idempotency_key' => $key], ['daily_summary_id' => $summary->id, 'document_kind' => 'daily_summary', 'status' => 'pending']);
        if (config('sunat.send_enabled', false) && ! in_array($submission->status, ['queued', 'processing', 'accepted'], true)) {
            $submission->update(['status' => 'queued', 'last_error' => null]);
            SendSunatDailySummary::dispatch($submission->id)->afterCommit();
        }

        return $submission->refresh();
    }
}
