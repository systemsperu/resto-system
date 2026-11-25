<?php

namespace Tests\Feature;

use App\Models\AccommodationRate;
use App\Models\AccommodationType;
use App\Models\AccommodationUnit;
use App\Models\Client;
use App\Models\HousekeepingTask;
use App\Models\User;
use App\Modules\ModuleManager;
use App\Services\Lodging\AvailabilityService;
use App\Services\Lodging\LodgingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LodgingPhaseNineTest extends TestCase
{
    use RefreshDatabase;

    public function test_motor_cotiza_y_excluye_una_unidad_reservada(): void
    {
        [$unit, $client, $admin] = $this->scenario();
        $service = app(LodgingService::class);
        $checkIn = CarbonImmutable::today()->addDays(2);
        $checkOut = $checkIn->addDays(2);

        $available = app(AvailabilityService::class)->available($checkIn, $checkOut, 2);
        $this->assertSame(300.0, $available->firstWhere('id', $unit->id)->quote_total);

        $service->reserve($this->reservationData($unit, $client, $checkIn, $checkOut), $admin);

        $this->assertFalse(app(AvailabilityService::class)->available($checkIn, $checkOut, 2)->contains('id', $unit->id));
    }

    public function test_bloqueo_de_unidad_impide_doble_reserva(): void
    {
        [$unit, $client, $admin] = $this->scenario();
        $checkIn = CarbonImmutable::today()->addDay();
        $checkOut = $checkIn->addDays(2);
        $service = app(LodgingService::class);
        $service->reserve($this->reservationData($unit, $client, $checkIn, $checkOut), $admin);

        $this->expectException(\DomainException::class);
        $service->reserve($this->reservationData($unit, $client, $checkIn, $checkOut), $admin);
    }

    public function test_recepcion_completa_entrada_salida_y_limpieza_desde_el_panel(): void
    {
        [$unit, $client] = $this->scenario();
        $reception = User::factory()->create(['role' => 'reception']);
        $cleaner = User::factory()->create(['role' => 'cleaning']);
        $this->enableLodging();
        $reservation = app(LodgingService::class)->reserve($this->reservationData($unit, $client, CarbonImmutable::today(), CarbonImmutable::tomorrow()), $reception);

        $this->actingAs($reception)->post(route('lodging.reservations.transition', $reservation), ['status' => 'checked_in'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('accommodation_units', ['id' => $unit->id, 'status' => 'occupied']);

        $this->actingAs($reception)->post(route('lodging.reservations.transition', $reservation), ['status' => 'checked_out'])->assertSessionHasNoErrors();
        $task = HousekeepingTask::firstOrFail();
        $this->assertDatabaseHas('accommodation_units', ['id' => $unit->id, 'status' => 'cleaning']);

        $this->actingAs($cleaner)->post(route('lodging.housekeeping.complete', $task))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('housekeeping_tasks', ['id' => $task->id, 'status' => 'completed']);
        $this->assertDatabaseHas('accommodation_units', ['id' => $unit->id, 'status' => 'available']);
    }

    public function test_anticipos_comparten_pagos_y_no_superan_saldo(): void
    {
        [$unit, $client, $admin] = $this->scenario();
        $service = app(LodgingService::class);
        $reservation = $service->reserve($this->reservationData($unit, $client, CarbonImmutable::today(), CarbonImmutable::tomorrow()), $admin);
        $payment = $service->addPayment($reservation, ['method' => 'yape', 'amount' => 50, 'reference' => 'PRUEBA-01'], $admin);

        $this->assertSame('lodging', $payment->source);
        $this->assertNull($payment->order_id);
        $this->assertSame($reservation->id, $payment->lodging_reservation_id);

        $this->expectException(\DomainException::class);
        $service->addPayment($reservation, ['method' => 'cash', 'amount' => 101], $admin);
    }

    public function test_mantenimiento_no_puede_pisar_reserva_activa(): void
    {
        [$unit, $client, $admin] = $this->scenario();
        $service = app(LodgingService::class);
        $checkIn = CarbonImmutable::today()->addDay();
        $checkOut = $checkIn->addDays(2);
        $service->reserve($this->reservationData($unit, $client, $checkIn, $checkOut), $admin);

        $this->expectException(\DomainException::class);
        $service->block($unit, ['starts_on' => $checkIn->toDateString(), 'ends_on' => $checkOut->toDateString(), 'reason' => 'maintenance'], $admin);
    }

    public function test_permisos_separan_recepcion_limpieza_y_configuracion(): void
    {
        $this->scenario();
        $this->enableLodging();
        $reception = User::factory()->create(['role' => 'reception']);
        $cleaner = User::factory()->create(['role' => 'cleaning']);

        $this->actingAs($reception)->get(route('lodging.index'))->assertOk();
        $this->actingAs($cleaner)->get(route('lodging.index'))->assertOk();
        $this->actingAs($reception)->post(route('lodging.types.store'), ['name' => 'No permitido'])->assertForbidden();
        $this->actingAs($cleaner)->post(route('lodging.reservations.store'), [])->assertForbidden();
    }

    public function test_consulta_publica_de_disponibilidad_no_expone_huespedes(): void
    {
        [$unit] = $this->scenario();
        $this->enableLodging();

        $this->getJson(route('lodging.availability', ['check_in' => today()->addDay()->toDateString(), 'check_out' => today()->addDays(2)->toDateString(), 'guests' => 2]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $unit->id)
            ->assertJsonMissingPath('data.0.client');
    }

    public function test_liberar_bloqueo_rehabilita_la_unidad(): void
    {
        [$unit, , $admin] = $this->scenario();
        $service = app(LodgingService::class);
        $block = $service->block($unit, ['starts_on' => today()->toDateString(), 'ends_on' => today()->addDay()->toDateString(), 'reason' => 'maintenance'], $admin);
        $this->assertDatabaseHas('accommodation_units', ['id' => $unit->id, 'status' => 'maintenance']);

        $service->releaseBlock($block, $admin);

        $this->assertDatabaseHas('accommodation_blocks', ['id' => $block->id]);
        $this->assertNotNull($block->fresh()->released_at);
        $this->assertDatabaseHas('accommodation_units', ['id' => $unit->id, 'status' => 'available']);
    }

    public function test_reporte_es_administrativo_y_limita_el_periodo(): void
    {
        [, , $admin] = $this->scenario();
        $this->enableLodging();
        $reception = User::factory()->create(['role' => 'reception']);

        $this->actingAs($reception)->get(route('lodging.report', ['from' => today()->toDateString(), 'to' => today()->addDay()->toDateString()]))->assertForbidden();
        $this->actingAs($admin)->get(route('lodging.report', ['from' => today()->toDateString(), 'to' => today()->addDay()->toDateString()]))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($admin)->get(route('lodging.report', ['from' => today()->toDateString(), 'to' => today()->addYears(2)->toDateString()]))->assertSessionHasErrors('to');
    }

    private function scenario(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::create(['name' => 'Cliente de prueba', 'source' => 'direct']);
        $type = AccommodationType::create(['name' => 'Búngalo', 'base_capacity' => 2, 'max_capacity' => 4]);
        $unit = AccommodationUnit::create(['accommodation_type_id' => $type->id, 'name' => 'Búngalo 1', 'code' => 'B01', 'capacity' => 4]);
        AccommodationRate::create(['accommodation_type_id' => $type->id, 'name' => 'Base', 'starts_on' => today()->subYear(), 'ends_on' => today()->addYear(), 'nightly_rate' => 150, 'minimum_nights' => 1]);

        return [$unit, $client, $admin];
    }

    private function reservationData(AccommodationUnit $unit, Client $client, CarbonImmutable $checkIn, CarbonImmutable $checkOut): array
    {
        return ['client_id' => $client->id, 'accommodation_unit_id' => $unit->id, 'check_in' => $checkIn->toDateString(), 'check_out' => $checkOut->toDateString(), 'adults' => 2, 'children' => 0];
    }

    private function enableLodging(): void
    {
        app(ModuleManager::class)->setEnabled('lodging', true);
    }
}
