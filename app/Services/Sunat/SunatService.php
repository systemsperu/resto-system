<?php

namespace App\Services\Sunat;

use App\Models\CreditNote;
use App\Models\DailySummary;
use App\Models\Order;
use Exception;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio principal de facturación electrónica - SUNAT (Perú).
 * Maneja firma, envío SOAP, persistencia de XML/CDR y trazabilidad de estados.
 */
class SunatService
{
    private SunatConfig $config;

    private ?See $see = null;

    public function __construct(?SunatConfig $config = null)
    {
        $this->config = $config ?? new SunatConfig;
    }

    /** ───────────────────────────────────────────────
     *  FACTURA / BOLETA
     *  ─────────────────────────────────────────────── */
    public function sendInvoice(Order $order): Order
    {
        $this->assertSendingEnabled();

        if (! $order->serie || ! $order->correlativo) {
            throw new \RuntimeException('La orden no tiene serie/correlativo asignados.');
        }

        $invoice = (new InvoiceBuilder($this->config))->build($order);

        if (config('sunat.mode') === 'fake') {
            return $this->acceptFakeInvoice($order, $invoice->getName());
        }

        try {
            $see = $this->getSee();
            $result = $see->send($invoice);
            $xml = $see->getXmlSigned($invoice);

            // 1. Guardar XML firmado
            $xmlName = $invoice->getName().'.xml';
            $xmlRelPath = 'sunat/xml/'.$xmlName;
            Storage::disk('local')->put($xmlRelPath, $xml);

            $order->xml_path = $xmlRelPath;
            $order->hash = $this->extractHash($xml);
            $order->sent_at = now();

            // 2. Procesar respuesta
            if ($result->isSuccess()) {
                $cdr = $result->getCdrResponse();
                $cdrZip = $result->getCdrZip();

                $cdrRelPath = 'sunat/cdr/R-'.$invoice->getName().'.zip';
                Storage::disk('local')->put($cdrRelPath, $cdrZip);

                $order->cdr_path = $cdrRelPath;
                $order->sunat_code = $cdr->getCode();
                $order->sunat_description = $cdr->getDescription();
                $order->sunat_status = $cdr->getCode() === '0' ? 'ACCEPTED' : 'OBSERVED';
            } else {
                $err = $result->getError();
                $order->sunat_status = 'REJECTED';
                $order->sunat_code = $err?->getCode();
                $order->sunat_description = $err?->getMessage();
            }
        } catch (Exception $e) {
            Log::error('SUNAT sendInvoice error', ['order_id' => $order->id, 'msg' => $e->getMessage()]);
            $order->sunat_status = 'ERROR';
            $order->sunat_code = '500';
            $order->sunat_description = $e->getMessage();
        }

        $order->save();

        return $order;
    }

    /** ───────────────────────────────────────────────
     *  NOTA DE CRÉDITO
     *  ─────────────────────────────────────────────── */
    public function sendCreditNote(CreditNote $cn): CreditNote
    {
        $this->assertSendingEnabled();

        $note = (new CreditNoteBuilder($this->config))->build($cn);

        if (config('sunat.mode') === 'fake') {
            return $this->acceptFakeDocument($cn, $note->getName());
        }

        try {
            $see = $this->getSee();
            $result = $see->send($note);
            $xml = $see->getXmlSigned($note);

            $xmlName = $note->getName().'.xml';
            $xmlRelPath = 'sunat/xml/'.$xmlName;
            Storage::disk('local')->put($xmlRelPath, $xml);

            $cn->xml_path = $xmlRelPath;
            $cn->hash = $this->extractHash($xml);
            $cn->sent_at = now();

            if ($result->isSuccess()) {
                $cdr = $result->getCdrResponse();
                $cdrZip = $result->getCdrZip();

                $cdrRelPath = 'sunat/cdr/R-'.$note->getName().'.zip';
                Storage::disk('local')->put($cdrRelPath, $cdrZip);

                $cn->cdr_path = $cdrRelPath;
                $cn->sunat_code = $cdr->getCode();
                $cn->sunat_description = $cdr->getDescription();
                $cn->sunat_status = $cdr->getCode() === '0' ? 'ACCEPTED' : 'OBSERVED';
            } else {
                $err = $result->getError();
                $cn->sunat_status = 'REJECTED';
                $cn->sunat_code = $err?->getCode();
                $cn->sunat_description = $err?->getMessage();
            }
        } catch (Exception $e) {
            Log::error('SUNAT sendCreditNote error', ['cn_id' => $cn->id, 'msg' => $e->getMessage()]);
            $cn->sunat_status = 'ERROR';
            $cn->sunat_code = '500';
            $cn->sunat_description = $e->getMessage();
        }

        $cn->save();

        return $cn;
    }

    /** ───────────────────────────────────────────────
     *  RESUMEN DIARIO (envío + consulta de ticket)
     *  ─────────────────────────────────────────────── */
    public function sendSummary(DailySummary $summaryModel, $greenterSummary): DailySummary
    {
        $this->assertSendingEnabled();

        if (config('sunat.mode') === 'fake') {
            $summaryModel->update(['sunat_status' => 'TICKET', 'ticket' => 'FAKE-'.$summaryModel->id, 'sent_at' => now()]);

            return $summaryModel->refresh();
        }

        try {
            $see = $this->getSee();
            $result = $see->send($greenterSummary);
            $xml = $see->getXmlSigned($greenterSummary);

            $xmlName = $greenterSummary->getName().'.xml';
            $xmlRelPath = 'sunat/xml/'.$xmlName;
            Storage::disk('local')->put($xmlRelPath, $xml);

            $summaryModel->xml_path = $xmlRelPath;
            $summaryModel->hash = $this->extractHash($xml);
            $summaryModel->sent_at = now();

            if ($result->isSuccess()) {
                $summaryModel->ticket = $result->getTicket();
                $summaryModel->sunat_status = 'TICKET';
            } else {
                $err = $result->getError();
                $summaryModel->sunat_status = 'REJECTED';
                $summaryModel->sunat_code = $err?->getCode();
                $summaryModel->sunat_description = $err?->getMessage();
            }
        } catch (Exception $e) {
            Log::error('SUNAT sendSummary error', ['summary_id' => $summaryModel->id, 'msg' => $e->getMessage()]);
            $summaryModel->sunat_status = 'ERROR';
            $summaryModel->sunat_code = '500';
            $summaryModel->sunat_description = $e->getMessage();
        }

        $summaryModel->save();

        return $summaryModel;
    }

    /**
     * Consulta el estado de un ticket de resumen.
     */
    public function checkSummaryTicket(DailySummary $summary): DailySummary
    {
        $this->assertSendingEnabled();

        if (config('sunat.mode') === 'fake') {
            $summary->update(['sunat_status' => 'ACCEPTED', 'sunat_code' => '0', 'sunat_description' => 'Aceptado por transporte simulado', 'consulted_at' => now()]);

            return $summary->refresh();
        }

        if (! $summary->ticket) {
            throw new \RuntimeException('El resumen no tiene ticket asignado.');
        }

        try {
            $see = $this->getSee();
            $result = $see->getStatus($summary->ticket);

            $summary->consulted_at = now();

            if ($result->isSuccess() && $result->getCdrResponse()) {
                $cdr = $result->getCdrResponse();
                $cdrZip = $result->getCdrZip();
                $cdrRel = 'sunat/cdr/R-'.$summary->identifier.'.zip';
                Storage::disk('local')->put($cdrRel, $cdrZip);

                $summary->cdr_path = $cdrRel;
                $summary->sunat_code = $cdr->getCode();
                $summary->sunat_description = $cdr->getDescription();
                $summary->sunat_status = $cdr->getCode() === '0' ? 'ACCEPTED' : 'OBSERVED';
            } else {
                $err = $result->getError();
                if ($err) {
                    $summary->sunat_status = 'REJECTED';
                    $summary->sunat_code = $err->getCode();
                    $summary->sunat_description = $err->getMessage();
                }
            }
        } catch (Exception $e) {
            Log::error('SUNAT checkSummaryTicket error', ['summary_id' => $summary->id, 'msg' => $e->getMessage()]);
            $summary->sunat_status = 'ERROR';
            $summary->sunat_code = '500';
            $summary->sunat_description = $e->getMessage();
        }

        $summary->save();

        return $summary;
    }

    /** ───────────────────────────────────────────────
     *  PDF (representación gráfica con QR)
     *  ─────────────────────────────────────────────── */
    public function buildQrPayload(Order $order): string
    {
        // Formato SUNAT: RUC|tipo|serie|correlativo|IGV|Total|FechaEmision|tipoDocCliente|nroDocCliente
        return implode('|', [
            $this->config->ruc(),
            $order->document_type === 'Factura' ? '01' : '03',
            $order->serie,
            $order->correlativo,
            number_format((float) $order->igv, 2, '.', ''),
            number_format((float) $order->total, 2, '.', ''),
            ($order->created_at ?? now())->format('Y-m-d'),
            $this->guessClientDocType($order->client_document),
            $order->client_document ?: '-',
        ]);
    }

    private function guessClientDocType(?string $doc): string
    {
        $doc = trim((string) $doc);
        if (strlen($doc) === 8) {
            return '1';
        }
        if (strlen($doc) === 11) {
            return '6';
        }

        return '0';
    }

    /** ───────────────────────────────────────────────
     *  Helpers internos
     *  ─────────────────────────────────────────────── */
    private function getSee(): See
    {
        if ($this->see) {
            return $this->see;
        }

        $see = new See;

        $cert = $this->loadCertificate();
        $see->setCertificate($cert);

        // Endpoint según ambiente
        $see->setService(config('sunat.mode') === 'production'
            ? SunatEndpoints::FE_PRODUCCION
            : SunatEndpoints::FE_BETA);

        // Credenciales SOL (en BETA el usuario debe ir como RUC+USER, ej: 20000000001MODDATOS)
        $see->setCredentials($this->config->solUser(), $this->config->solPass());

        return $this->see = $see;
    }

    /**
     * Impide cualquier comunicación externa mientras la instalación no haya
     * superado la validación de seguridad y facturación electrónica.
     */
    private function assertSendingEnabled(): void
    {
        if (! config('sunat.send_enabled', false)) {
            throw new \RuntimeException(
                'Los envíos a SUNAT están deshabilitados en esta instalación.'
            );
        }

        $mode = config('sunat.mode', 'disabled');
        if (! in_array($mode, ['fake', 'beta', 'production'], true)) {
            throw new \RuntimeException('El modo de transporte SUNAT no es válido.');
        }
        if ($mode === 'production' && (! config('sunat.production_enabled') || ! app()->environment('production'))) {
            throw new \RuntimeException('El transporte SUNAT de producción requiere doble habilitación y entorno productivo.');
        }
    }

    private function acceptFakeInvoice(Order $order, string $name): Order
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><Invoice><ID>'.e($name).'</ID><ds:DigestValue>'.hash('sha256', $name).'</ds:DigestValue></Invoice>';
        $xmlPath = 'sunat/xml/'.$name.'.xml';
        $cdrPath = 'sunat/cdr/R-'.$name.'.zip';
        Storage::disk('local')->put($xmlPath, $xml);
        Storage::disk('local')->put($cdrPath, 'CDR FAKE PARA PRUEBAS');
        $order->update(['xml_path' => $xmlPath, 'cdr_path' => $cdrPath, 'hash' => hash('sha256', $name), 'sent_at' => now(), 'sunat_status' => 'ACCEPTED', 'sunat_code' => '0', 'sunat_description' => 'Aceptado por transporte simulado']);

        return $order->refresh();
    }

    private function acceptFakeDocument(CreditNote $note, string $name): CreditNote
    {
        $xmlPath = 'sunat/xml/'.$name.'.xml';
        $cdrPath = 'sunat/cdr/R-'.$name.'.zip';
        Storage::disk('local')->put($xmlPath, '<CreditNote><ID>'.e($name).'</ID></CreditNote>');
        Storage::disk('local')->put($cdrPath, 'CDR FAKE PARA PRUEBAS');
        $note->update(['xml_path' => $xmlPath, 'cdr_path' => $cdrPath, 'hash' => hash('sha256', $name), 'sent_at' => now(), 'sunat_status' => 'ACCEPTED', 'sunat_code' => '0', 'sunat_description' => 'Aceptado por transporte simulado']);

        return $note->refresh();
    }

    /**
     * Carga el certificado. Si no hay uno configurado, usa el demo auto-firmado
     * que se genera en storage/app/sunat/certs/demo.pem (válido solo para BETA).
     */
    private function loadCertificate(): string
    {
        $path = $this->config->certPath();

        if ($path && file_exists($path)) {
            // Si es .pfx, convertirlo a PEM
            if (str_ends_with(strtolower($path), '.pfx')) {
                return $this->pfxToPem($path, $this->config->certPassword());
            }

            return file_get_contents($path);
        }

        // Fallback: certificado demo auto-firmado en storage (solo BETA)
        $demo = 'sunat/certs/demo.pem';
        if (Storage::disk('local')->exists($demo)) {
            return Storage::disk('local')->get($demo);
        }

        throw new \RuntimeException(
            'No se encontró certificado digital. '.
            'Para PRODUCCIÓN: suba su .pfx en Configuración. '.
            'Para BETA: ejecute "php artisan sunat:cert:demo" para generar uno de prueba.'
        );
    }

    private function pfxToPem(string $pfxPath, string $password): string
    {
        $pfx = file_get_contents($pfxPath);
        $certs = [];

        if (! openssl_pkcs12_read($pfx, $certs, $password)) {
            throw new \RuntimeException('No se pudo leer el certificado .pfx (revise la contraseña).');
        }

        return $certs['cert'].PHP_EOL.$certs['pkey'];
    }

    private function extractHash(string $xml): ?string
    {
        // Extrae el DigestValue (hash) del XML firmado
        if (preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/', $xml, $m)) {
            return $m[1];
        }

        return null;
    }
}
