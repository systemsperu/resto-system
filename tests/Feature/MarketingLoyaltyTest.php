<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\ClientConsent;
use App\Models\Coupon;
use App\Models\CustomerSegment;
use App\Models\User;
use App\Services\Marketing\CampaignService;
use App\Services\Marketing\CouponService;
use App\Services\Marketing\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingLoyaltyTest extends TestCase
{
    use RefreshDatabase;

    public function test_campana_solo_incluye_clientes_con_consentimiento_vigente(): void
    {
        $allowed = Client::create(['name' => 'Autorizado', 'email' => 'si@example.test']);
        $blocked = Client::create(['name' => 'Sin permiso', 'email' => 'no@example.test']);
        ClientConsent::create(['client_id' => $allowed->id, 'channel' => 'email', 'granted' => true, 'recorded_at' => now()->subMinute()]);
        ClientConsent::create(['client_id' => $blocked->id, 'channel' => 'email', 'granted' => true, 'recorded_at' => now()->subMinutes(2)]);
        ClientConsent::create(['client_id' => $blocked->id, 'channel' => 'email', 'granted' => false, 'recorded_at' => now()]);
        $segment = CustomerSegment::create(['name' => 'Prueba']);
        $segment->clients()->sync([$allowed->id, $blocked->id]);
        $campaign = Campaign::create(['name' => 'Campaña segura', 'channel' => 'email', 'message' => 'Contenido', 'customer_segment_id' => $segment->id]);

        $result = (new CampaignService)->simulate($campaign);

        $this->assertSame(['simulated' => 1, 'skipped' => 1], $result);
        $this->assertDatabaseHas('campaign_deliveries', ['client_id' => $allowed->id, 'status' => 'simulated']);
        $this->assertDatabaseHas('campaign_deliveries', ['client_id' => $blocked->id, 'status' => 'skipped']);
    }

    public function test_simular_dos_veces_no_duplica_entregas(): void
    {
        $client = Client::create(['name' => 'Cliente', 'phone' => '999111222']);
        ClientConsent::create(['client_id' => $client->id, 'channel' => 'whatsapp', 'granted' => true, 'recorded_at' => now()]);
        $campaign = Campaign::create(['name' => 'Repetición', 'channel' => 'whatsapp', 'message' => 'Hola']);

        (new CampaignService)->simulate($campaign);
        (new CampaignService)->simulate($campaign);

        $this->assertDatabaseCount('campaign_deliveries', 1);
    }

    public function test_cupon_respeta_idempotencia_limites_y_calculo(): void
    {
        $client = Client::create(['name' => 'Cliente']);
        $coupon = Coupon::create(['code' => 'PROMO10', 'name' => 'Diez por ciento', 'discount_type' => 'percentage', 'discount_value' => 10, 'minimum_amount' => 50, 'per_client_limit' => 1]);
        $service = new CouponService;

        $first = $service->redeem($coupon, $client, 100, 'venta-uno');
        $same = $service->redeem($coupon, $client, 100, 'venta-uno');

        $this->assertSame($first->id, $same->id);
        $this->assertSame('10.00', $first->discount_amount);
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->expectExceptionMessage('agotó el límite');
        $service->redeem($coupon, $client, 100, 'venta-dos');
    }

    public function test_puntos_son_idempotentes_y_no_permiten_saldo_negativo(): void
    {
        $client = Client::create(['name' => 'Cliente fiel']);
        $service = new LoyaltyService;
        $service->apply($client, 100, 'earn', 'compra-1');
        $service->apply($client, 100, 'earn', 'compra-1');
        $service->apply($client, 40, 'redeem', 'canje-1');

        $this->assertSame(60, $client->loyaltyAccount->points_balance);
        $this->assertDatabaseCount('loyalty_transactions', 2);
        $this->expectExceptionMessage('insuficiente');
        $service->apply($client, 100, 'redeem', 'canje-2');
    }

    public function test_solo_administrador_registra_consentimiento(): void
    {
        $client = Client::create(['name' => 'Cliente']);
        $waiter = User::factory()->create(['role' => 'waiter']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($waiter)->post(route('clients.consent', $client), ['channel' => 'email', 'granted' => 1])->assertForbidden();
        $this->actingAs($admin)->post(route('clients.consent', $client), ['channel' => 'email', 'granted' => 1])->assertRedirect();
        $this->assertTrue($client->hasConsent('email'));
        $this->assertDatabaseHas('system_audits', ['action' => 'client.consent', 'status' => 'granted']);
    }
}
