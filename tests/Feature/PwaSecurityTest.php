<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_contains_installable_brand_assets(): void
    {
        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('display', 'standalone')->assertJsonCount(2, 'icons');
        $this->get('/pwa/icon/192.png')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/pwa/icon/512.png')->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_offline_shell_and_service_worker_are_public(): void
    {
        $this->get('/offline')->assertOk()->assertSee('Sin acceso al servidor');
        $this->assertFileExists(public_path('sw.js'));
    }

    public function test_authenticated_pages_are_never_cacheable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get('/pwa/status');

        $response->assertOk();

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    public function test_logout_requests_browser_data_cleanup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/logout')->assertRedirect('/login')->assertHeader('Clear-Site-Data', '"cache", "storage"');
    }
}
