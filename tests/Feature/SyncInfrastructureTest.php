<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\SyncOutbox;
use App\Services\Sync\SyncPublisher;
use App\Services\Sync\SyncPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sync.mode' => 'local_sync', 'sync.installation_uuid' => '10000000-0000-4000-8000-000000000001', 'sync.device_uuid' => '20000000-0000-4000-8000-000000000001']);
    }

    public function test_outbox_is_idempotent_and_excludes_secrets(): void
    {
        $publisher = app(SyncPublisher::class);
        $first = $publisher->publish('Order', 10, 'order.updated', ['total' => 20, 'password' => 'prohibido'], 7);
        $second = $publisher->publish('Order', 10, 'order.updated', ['total' => 20], 7);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, SyncOutbox::count());
        $this->assertArrayNotHasKey('password', $first->payload);
    }

    public function test_disabled_sync_never_calls_network(): void
    {
        Http::fake();
        config(['sync.enabled' => false]);
        $this->assertSame(0, app(SyncPushService::class)->push());
        Http::assertNothingSent();
    }

    public function test_acknowledged_event_is_not_sent_twice(): void
    {
        config(['sync.enabled' => true, 'sync.endpoint' => 'https://sync.example.test', 'sync.token' => 'test-token']);
        $event = app(SyncPublisher::class)->publish('Reservation', 5, 'reservation.created', ['status' => 'pending'], 1);
        Http::fake(['*' => Http::response(['acked' => [$event->event_uuid]])]);

        $this->assertSame(1, app(SyncPushService::class)->push());
        $this->assertSame('acked', $event->refresh()->status);
        $this->assertSame(0, app(SyncPushService::class)->push());
        Http::assertSentCount(1);
    }

    public function test_cloud_reservation_command_is_received_once(): void
    {
        config(['sync.enabled' => true, 'sync.token' => 'receiver-token']);
        $payload = ['client_name' => 'Cliente de prueba', 'reservation_time' => now()->addDay()->toIso8601String(), 'people' => 4];
        $event = ['event_uuid' => '30000000-0000-4000-8000-000000000001', 'installation_uuid' => '40000000-0000-4000-8000-000000000001', 'event_type' => 'reservation.command.create', 'payload' => $payload, 'checksum' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))];

        $this->withToken('receiver-token')->postJson('/api/sync/events', ['events' => [$event]])->assertOk()->assertJson(['acked' => [$event['event_uuid']]]);
        $this->withToken('receiver-token')->postJson('/api/sync/events', ['events' => [$event]])->assertOk();
        $this->assertSame(1, Reservation::count());
    }
}
