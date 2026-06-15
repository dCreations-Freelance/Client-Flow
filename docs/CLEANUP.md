# CLEANUP.md — Reset del proyecto ClientFlow a Laravel vacio

Este documento describe como dejar el proyecto en estado de Laravel 13 limpio, eliminando todo el codigo custom manteniendo intacto el esqueleto base de Laravel, Docker, Tailwind y Livewire.

## Que mantiene este reset

Se mantiene:
- Laravel 13 (composer dependencies core)
- Livewire 4
- Tailwind CSS 4 (con la paleta warm actual)
- Docker local (PHP-FPM, Nginx, MySQL, Node)
- La fuente Instrument Sans
- Variables de entorno base
- Estructura de directorios Laravel

## Archivos a BORRAR

### Controladores custom

```bash
rm app/Http/Controllers/Admin/ClientController.php
rm app/Http/Controllers/Admin/ClientInvitationController.php
rm app/Http/Controllers/Admin/ProjectController.php
rm app/Http/Controllers/Admin/ProjectUpdateController.php
rm app/Http/Controllers/Admin/VisualEntryController.php
rm app/Http/Controllers/InvitationAcceptanceController.php
rm app/Http/Controllers/Portal/ProjectTimelineController.php
rm app/Http/Controllers/Portal/VisualEntryController.php
rm app/Http/Controllers/VisualEntryMediaController.php
rm -rf app/Http/Controllers/Admin/
rm -rf app/Http/Controllers/Portal/
rm -rf app/Http/Controllers/Auth/
```

### Modelos custom

```bash
rm app/Models/Client.php
rm app/Models/ClientInvitation.php
rm app/Models/Project.php
rm app/Models/ProjectUpdate.php
rm app/Models/VisualEntry.php
```

### Enums custom

```bash
rm -rf app/Enums/
```

### Middleware custom

```bash
rm app/Http/Middleware/EnsureUserIsAdmin.php
rm app/Http/Middleware/EnsureUserIsClient.php
```

### Comandos custom

```bash
rm app/Console/Commands/CreateAdminUser.php
```

### Notificaciones custom

```bash
rm -rf app/Notifications/
```

### Vistas custom

```bash
rm -rf resources/views/admin/
rm -rf resources/views/portal/
rm -rf resources/views/auth/
rm -rf resources/views/components/layouts/
rm -rf resources/views/partials/
```

### Migraciones custom

```bash
rm database/migrations/2026_06_08_000001_create_clients_table.php
rm database/migrations/2026_06_08_000002_create_client_invitations_table.php
rm database/migrations/2026_06_08_000003_create_projects_table.php
rm database/migrations/2026_06_09_000001_create_project_updates_table.php
rm database/migrations/2026_06_09_000002_create_visual_entries_table.php
```

### Factories custom

```bash
rm database/factories/ClientFactory.php
rm database/factories/ClientInvitationFactory.php
rm database/factories/ProjectFactory.php
rm database/factories/ProjectUpdateFactory.php
rm database/factories/VisualEntryFactory.php
```

### Tests custom

```bash
rm -rf tests/Feature/Admin/
rm tests/Feature/Auth/AuthenticationTest.php
rm tests/Feature/Auth/
rm tests/Feature/InvitationAcceptanceTest.php
rm tests/Feature/RoleAccessTest.php
rm -rf tests/Feature/Auth/
```

### Build artifacts

```bash
rm -rf public/build/
rm -f public/fonts-manifest.dev.json
```

### Storage artifacts

```bash
rm -f database/database.sqlite
rm -rf storage/framework/views/*.php
rm -f storage/logs/laravel.log
rm -rf storage/app/clientflow/
```

## Archivos a MODIFICAR (revertir a defaults)

### 1. `app/Models/User.php`

Revertir al User model default de Laravel 13 sin role, sin isAdmin/isClient, sin relacion Client:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### 2. `database/migrations/0001_01_01_000000_create_users_table.php`

Eliminar la linea `$table->string('role')->default('client')->index();` y cualquier referencia al campo role.

### 3. `database/factories/UserFactory.php`

Eliminar la importacion de UserRole y las definiciones `admin()` y `client()`. Dejar solo la definition base:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
```

### 4. `bootstrap/app.php`

Revertir a defaults. Eliminar:
- El registro de `CreateAdminUser::class`
- Los middleware aliases `admin` y `client`
- Los `->withExceptions` custom para JSON
- Cualquier otra personalizacion

Dejar:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
```

### 5. `routes/web.php`

Revertir a una sola ruta welcome:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
```

### 6. `resources/views/welcome.blade.php`

Revertir al welcome default de Laravel 13 o dejar un placeholder minimo:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClientFlow</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen flex items-center justify-center bg-[#FAFAF7]">
        <h1 class="text-3xl font-semibold text-[#111827]">ClientFlow</h1>
    </div>
</body>
</html>
```

### 7. `resources/css/app.css`

Mantener el theme actual con Instrument Sans (es el estilo que nos gusta):

```css
@import 'tailwindcss';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}
```

### 8. `vite.config.js`

Mantener la config actual con Tailwind y Instrument Sans fonts, pero eliminar customizaciones de server si las hay:

```js
import { defineConfig } from 'vite';
import laravel, { refresh } from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { fonts } from 'laravel-vite-plugin/fonts';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }).fonts(fonts({ bunny: { 'Instrument Sans': [400, 500, 600] } })),
        tailwindcss(),
    ],
});
```

NOTA: Si se usa bunny helper, verificar que el import de `refresh` y `fonts` este correcto segun la version de laravel-vite-plugin.

### 9. `composer.json`

Mantener:
- `laravel/framework ^13.8`
- `livewire/livewire ^4.3`
- `laravel/tinker ^3.0`
- `laravel/pail ^1.2.5` (opcional, util para logs)
- `laravel/pao ^1.0.6` (opcional)
- Scripts de test

Eliminar si existen paquetes que solo pertenecian al codigo custom.

### 10. `.env` y `.env.example`

Mantener los valores de Docker:
- `APP_NAME=ClientFlow`
- `APP_URL=http://localhost:8080`
- `DB_CONNECTION=mysql`
- `DB_HOST=mysql` (Docker) / `127.0.0.1` (local)
- `DB_PORT=3306`
- `DB_DATABASE=clientflow`
- `DB_USERNAME=clientflow`
- `DB_PASSWORD=clientflow`
- `APP_LOCALE=es`
- `APP_FAKER_LOCALE=es_ES`

### 11. `package.json`

Mantener:
- `@tailwindcss/vite ^4.0.0`
- `tailwindcss ^4.0.0`
- `laravel-vite-plugin ^3.1`
- `vite ^8.0.0`

Eliminar `concurrently` si no se necesita.

## Archivos que NO se tocan

- `app/Http/Controllers/Controller.php` (base controller de Laravel)
- `app/Http/Kernel.php` (si existe)
- `bootstrap/providers.php`
- `config/*` (todos los configs de Laravel)
- `database/migrations/0001_01_01_000001_create_cache_table.php`
- `database/migrations/0001_01_01_000002_create_jobs_table.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/js/app.js`
- `routes/console.php`
- `tests/TestCase.php`
- `tests/Feature/ExampleTest.php`
- `tests/Unit/ExampleTest.php`
- `public/.htaccess`
- `artisan`
- `composer.json` / `composer.lock`
- `package.json` / `package-lock.json`
- `phpunit.xml`
- Todo en `config/`, `bootstrap/`, `storage/framework/`

## Comandos post-reset

Despues de ejecutar el cleanup:

```bash
cd app

# Regenerar autoload
composer dump-autoload

# Refrescar base de datos
php artisan migrate:fresh

# Limpiar cache
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Verificar que funciona
php artisan route:list
php artisan test

# Reconstruir frontend
npm run build
```

Con Docker:

```bash
docker compose exec app composer dump-autoload
docker compose exec app php artisan migrate:fresh
docker compose exec app php artisan view:clear
docker compose exec app php artisan cache:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan test
docker compose exec node npm run build
```

## Verificacion final

Despues del reset, la app debe:

1. Arrancar sin errores en `php artisan serve` o Docker.
2. Mostrar una pagina welcome minima en `/`.
3. Tener solo 3 rutas: `/`, `/up` (health), y la ruta inspire de console.
4. No tener tablas custom en la base de datos (solo users, cache, jobs, sessions, password_reset_tokens).
5. No tener modelos, controllers, ni middleware custom.
6. Tener Livewire y Tailwind instalados y funcionando.
7. Tener Docker funcionando con PHP-FPM, Nginx, MySQL y Node.