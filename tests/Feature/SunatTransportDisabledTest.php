<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Sunat\SunatConfig;
use App\Services\Sunat\SunatService;
use RuntimeException;
use Tests\TestCase;

class SunatTransportDisabledTest extends TestCase
{
    public function test_el_transporte_sunat_permanece_bloqueado_por_defecto(): void
    {
        config()->set('sunat.send_enabled', false);

        $order = new Order([
            'document_type' => 'Boleta',
            'serie' => 'B001',
            'correlativo' => 1,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Los envíos a SUNAT están deshabilitados');

        (new SunatService(new SunatConfig([])))->sendInvoice($order);
    }
}
