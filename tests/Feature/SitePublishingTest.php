<?php

namespace Tests\Feature;

use App\Models\SitePage;
use App\Models\User;
use App\Modules\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePublishingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleManager::class)->setEnabled('landing', true);
    }

    public function test_only_published_home_page_is_public(): void
    {
        SitePage::create(['title' => 'Borrador', 'slug' => 'borrador', 'is_home' => true]);
        $this->get('/sitio')->assertNotFound();

        SitePage::query()->update(['published_at' => now()]);
        $this->get('/sitio')->assertOk()->assertSee('Borrador');
    }

    public function test_admin_can_create_and_publish_a_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/site-admin/pages', [
            'title' => 'Inicio', 'slug' => 'inicio', 'is_home' => '1',
            'published' => '1', 'show_in_navigation' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('site_pages', ['slug' => 'inicio', 'is_home' => true]);
        $this->get('/sitio')->assertOk()->assertSee('Inicio');
    }

    public function test_non_admin_cannot_manage_site_content(): void
    {
        $waiter = User::factory()->create(['role' => 'waiter']);

        $this->actingAs($waiter)->post('/site-admin/pages', [
            'title' => 'No autorizado', 'slug' => 'no-autorizado',
        ])->assertForbidden();
    }
}
