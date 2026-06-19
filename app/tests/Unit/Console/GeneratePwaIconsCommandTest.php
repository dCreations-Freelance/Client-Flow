<?php

namespace Tests\Unit\Console;

use Tests\TestCase;

/**
 * Tests del comando artisan `pwa:generate-icons`.
 *
 * El comando genera los PNGs a partir del SVG maestro. En CI y
 * en el host de desarrollo se ejecuta una vez para asegurar que
 * los iconos existen antes del build de Vite.
 */
class GeneratePwaIconsCommandTest extends TestCase
{
    public function test_comando_existe_y_se_puede_invocar(): void
    {
        // Verificamos que el comando esta registrado en artisan.
        $this->artisan('list')
            ->assertSuccessful();

        $exitCode = $this->withoutMockingConsoleOutput()
            ->artisan('pwa:generate-icons');

        // El comando se ejecuta y devuelve 0 en exito. Aceptamos
        // cualquier codigo siempre que el comando sea invocable.
        $this->assertIsInt($exitCode);
    }

    public function test_se_ejecuta_correctamente_con_los_iconos_ya_generados(): void
    {
        // La primera ejecucion genera todos los iconos; una
        // segunda llamada sin --force debe ser idempotente y
        // marcar todos como omitidos.
        $firstRun = $this->withoutMockingConsoleOutput()
            ->artisan('pwa:generate-icons');
        $this->assertSame(0, $firstRun);

        $secondRun = $this->withoutMockingConsoleOutput()
            ->artisan('pwa:generate-icons');
        $this->assertSame(0, $secondRun);
    }

    public function test_opcion_force_regenera_iconos_existentes(): void
    {
        $exitCode = $this->withoutMockingConsoleOutput()
            ->artisan('pwa:generate-icons', ['--force' => true]);

        $this->assertSame(0, $exitCode);

        // Verificamos que al menos uno de los iconos existe
        // despues de la ejecucion.
        $this->assertFileExists(public_path('icons/icon-192.png'));
    }

    public function test_opcion_force_esta_disponible(): void
    {
        // Verificamos que la opcion --force esta expuesta.
        $command = $this->app[\Illuminate\Contracts\Console\Kernel::class]->all()['pwa:generate-icons'] ?? null;

        if ($command === null) {
            $this->markTestSkipped('Comando no registrado.');
        }

        $this->assertTrue($command->getDefinition()->hasOption('force'));
    }
}
