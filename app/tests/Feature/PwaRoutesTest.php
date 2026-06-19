<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests del manifest y del service worker de la PWA.
 *
 * Cubren: rutas publicas, content-type correcto, header
 * `Service-Worker-Allowed: /` para el SW, campos requeridos
 * del manifest (name, start_url, display, icons) y
 * cache-control diferenciado entre manifest y SW.
 */
class PwaRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_es_publico_y_devuelve_json_valido(): void
    {
        $response = $this->get(route('pwa.manifest'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');

        $data = $response->json();

        $this->assertSame('ClientFlow', $data['name']);
        $this->assertSame('ClientFlow', $data['short_name']);
        $this->assertSame('standalone', $data['display']);
        $this->assertSame(route('home'), $data['start_url']);
        $this->assertSame('/', $data['scope']);
        $this->assertSame('#FAFAF7', $data['theme_color']);
    }

    public function test_manifest_incluye_los_iconos_requeridos(): void
    {
        $response = $this->get(route('pwa.manifest'));

        $data = $response->json();

        $this->assertArrayHasKey('icons', $data);
        $this->assertGreaterThanOrEqual(2, count($data['icons']));

        $sizes = array_column($data['icons'], 'sizes');
        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
    }

    public function test_manifest_incluye_shortcuts_para_acceso_rapido(): void
    {
        $response = $this->get(route('pwa.manifest'));

        $shortcuts = $response->json('shortcuts');
        $this->assertIsArray($shortcuts);
        $this->assertGreaterThanOrEqual(1, count($shortcuts));
    }

    public function test_manifest_cachea_una_hora(): void
    {
        $response = $this->get(route('pwa.manifest'));

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=3600', $cacheControl);
    }

    public function test_sw_es_publico_y_devuelve_javascript(): void
    {
        $response = $this->get(route('pwa.sw'));

        $response->assertOk();
        $this->assertStringContainsString('javascript', $response->headers->get('Content-Type'));
    }

    public function test_sw_no_se_cachea_en_cliente(): void
    {
        $response = $this->get(route('pwa.sw'));

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
    }

    public function test_sw_declara_header_service_worker_allowed(): void
    {
        $response = $this->get(route('pwa.sw'));

        $this->assertSame('/', $response->headers->get('Service-Worker-Allowed'));
    }

    public function test_sw_contiene_los_handlers_basicos(): void
    {
        $response = $this->get(route('pwa.sw'));

        $body = $response->getContent();
        $this->assertStringContainsString("addEventListener('install'", $body);
        $this->assertStringContainsString("addEventListener('activate'", $body);
        $this->assertStringContainsString("addEventListener('fetch'", $body);
        $this->assertStringContainsString("addEventListener('message'", $body);
        $this->assertStringContainsString("addEventListener('notificationclick'", $body);
    }

    public function test_sw_no_cachea_rutas_autenticadas(): void
    {
        $response = $this->get(route('pwa.sw'));

        $body = $response->getContent();
        // El SW debe ignorar explicitamente admin/portal/api/livewire
        // para no romper CSRF ni sesiones.
        $this->assertStringContainsString("'/admin'", $body);
        $this->assertStringContainsString("'/portal'", $body);
        $this->assertStringContainsString("'/api'", $body);
        $this->assertStringContainsString("'/livewire'", $body);
    }

    public function test_manifest_y_sw_no_requieren_autenticacion(): void
    {
        // Sin usuario autenticado, las dos rutas deben responder 200.
        // Esto es importante para que el navegador pueda instalar la
        // PWA desde la landing o desde paginas de auth.
        $this->assertGuest();
        $this->get(route('pwa.manifest'))->assertOk();
        $this->get(route('pwa.sw'))->assertOk();
    }
}
