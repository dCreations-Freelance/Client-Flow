<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/**
 * Sirve los assets de la PWA: manifest y service worker.
 *
 * Las dos rutas son publicas (sin middleware `auth`) porque:
 * - El manifest debe estar disponible antes del login para que
 *   el navegador pueda mostrar la PWA en "Anadir a pantalla de
 *   inicio" desde la landing.
 * - El service worker se registra desde cualquier pagina, asi
 *   que la ruta `/sw.js` debe responder aunque el usuario no
 *   este autenticado.
 *
 * El service worker se sirve con `Service-Worker-Allowed: /`
 * para que su scope cubra todo el sitio aunque viva en una
 *   ruta especifica, y con `Cache-Control: no-cache, no-store,
 *   must-revalidate` para que el navegador siempre pida la
 *   version mas reciente (asi los deploys invalidan el SW
 *   automaticamente).
 */
class PwaController extends Controller
{
    /**
     * Tamano maximo de la cache del manifest. Una hora es
     * suficiente para amortiguar peticiones pero permite que
     * cambios en el nombre o en los iconos se propaguen rapido
     * sin necesidad de versionar la URL.
     */
    private const MANIFEST_CACHE_MAX_AGE = 3600;

    /**
     * Devuelve el `manifest.webmanifest` con la configuracion de
     * la PWA: nombre, colores, iconos, start_url, display, etc.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function manifest(): JsonResponse
    {
        $name = config('app.name', 'ClientFlow');
        $shortName = 'ClientFlow';

        $manifest = [
            'name' => $name,
            'short_name' => $shortName,
            'description' => 'Portal privado para clientes: seguimiento visual, entregables y aprobaciones.',
            'start_url' => route('home'),
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait-primary',
            'theme_color' => '#FAFAF7',
            'background_color' => '#FAFAF7',
            'lang' => app()->getLocale(),
            'dir' => 'ltr',
            'categories' => ['business', 'productivity'],
            'icons' => [
                [
                    'src' => asset('icons/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('icons/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => asset('icons/icon-maskable-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'Panel',
                    'short_name' => 'Panel',
                    'description' => 'Acceder al panel de administracion',
                    'url' => route('admin.dashboard'),
                ],
                [
                    'name' => 'Mis proyectos',
                    'short_name' => 'Proyectos',
                    'description' => 'Ver el listado de proyectos',
                    'url' => route('portal.projects.index'),
                ],
            ],
        ];

        return response()->json($manifest, 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age='.self::MANIFEST_CACHE_MAX_AGE,
        ]);
    }

    /**
     * Sirve el service worker con los headers correctos para que
     * el navegador lo registre correctamente. Lee el archivo
     * desde `resources/js/sw.js` (fuera del bundle de Vite) para
     * que ningun bundler modifique los paths.
     *
     * El header `Service-Worker-Allowed: /` es critico: por
     * defecto un SW solo puede controlar el directorio donde
     * vive. Sin ese header el SW quedaria con scope `/` solo si
     * se sirve desde la raiz; al estar en una ruta Laravel este
     * header "amplia" el scope.
     *
     * @return \Illuminate\Http\Response
     */
    public function serviceWorker(): Response
    {
        $path = resource_path('js/sw.js');

        if (! File::exists($path)) {
            abort(404, 'Service worker no encontrado.');
        }

        $content = File::get($path);

        return response($content, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Service-Worker-Allowed' => '/',
        ]);
    }
}
