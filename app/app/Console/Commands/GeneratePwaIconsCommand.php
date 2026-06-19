<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Genera los iconos PNG de la PWA.
 *
 * Como GD no soporta SVG nativamente y mantener Imagick como
 * dependencia opcional es fragil, este comando genera los PNGs
 * dibujando directamente con GD a partir de primitivas. La
 * composicion es deliberadamente simple: un cuadrado con bordes
 * redondeados, un fondo oscuro y un "CF" estilizado con la
 * paleta warm del proyecto. Suficiente para un MVP; en una fase
 * futura se puede sustituir por un set de iconos disenados a
 * mano en Figma.
 *
 * El comando es idempotente: solo genera los archivos que no
 * existen. Con `--force` regenera siempre. En CI se ejecuta
 * una vez antes del build de Vite para que los iconos esten
 * siempre disponibles.
 */
class GeneratePwaIconsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'pwa:generate-icons
                            {--force : Regenera los iconos incluso si ya existen}';

    /**
     * @var string
     */
    protected $description = 'Genera los iconos PNG de la PWA';

    /**
     * Tamanos de icono a generar. Cada entrada es
     * `[nombre_archivo, lado]`.
     *
     * @var list<array{0: string, 1: int}>
     */
    private const ICON_SIZES = [
        ['icon-16.png', 16],
        ['icon-32.png', 32],
        ['icon-48.png', 48],
        ['icon-192.png', 192],
        ['icon-512.png', 512],
        ['apple-touch-icon.png', 180],
    ];

    /**
     * Colores de la paleta warm del proyecto. Usados para
     * mantener consistencia visual con el resto de la app.
     */
    private const COLOR_BACKGROUND = [250, 250, 247];
    private const COLOR_DARK = [17, 24, 39];
    private const COLOR_ACCENT = [184, 135, 70];

    /**
     * @return int
     */
    public function handle(): int
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->error('GD no esta disponible. Instala php-gd para generar los iconos.');

            return self::FAILURE;
        }

        $outputDir = public_path('icons');

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $force = (bool) $this->option('force');
        $generated = 0;
        $skipped = 0;

        foreach (self::ICON_SIZES as [$filename, $size]) {
            $outputPath = "{$outputDir}/{$filename}";

            if (file_exists($outputPath) && ! $force) {
                $skipped++;
                continue;
            }

            if (! $this->renderIcon($outputPath, $size, false)) {
                $this->error("Fallo al generar {$filename}.");

                return self::FAILURE;
            }

            $generated++;
            $this->line("  - {$filename} ({$size}x{$size})");
        }

        // Icono maskable: padding del 40% para que el SO pueda
        // recortarlo sin perder contenido.
        $maskablePath = "{$outputDir}/icon-maskable-512.png";
        if (! file_exists($maskablePath) || $force) {
            if (! $this->renderIcon($maskablePath, 512, true)) {
                $this->error('Fallo al generar icon-maskable-512.png.');

                return self::FAILURE;
            }
            $generated++;
            $this->line('  - icon-maskable-512.png (512x512 maskable)');
        } else {
            $skipped++;
        }

        $this->info(sprintf(
            'Iconos generados: %d (omitidos: %d). Salida: %s',
            $generated,
            $skipped,
            $outputDir,
        ));

        return self::SUCCESS;
    }

    /**
     * Dibuja un icono simple con la paleta del proyecto. Si
     * `maskable` es true, escala el contenido al 60% central y
     * deja el resto como fondo blanco para que el SO pueda
     * recortarlo sin perder el isotipo.
     *
     * @param  string  $outputPath
     * @param  int  $size
     * @param  bool  $maskable
     * @return bool
     */
    private function renderIcon(string $outputPath, int $size, bool $maskable): bool
    {
        $image = imagecreatetruecolor($size, $size);
        if ($image === false) {
            return false;
        }

        // Asignar color de fondo warm.
        $bgColor = imagecolorallocate(
            $image,
            self::COLOR_BACKGROUND[0],
            self::COLOR_BACKGROUND[1],
            self::COLOR_BACKGROUND[2]
        );
        imagefill($image, 0, 0, $bgColor);

        // Si es maskable, el area central es mas pequena.
        $innerSize = $maskable ? (int) ($size * 0.6) : (int) ($size * 0.75);
        $innerX = (int) (($size - $innerSize) / 2);
        $innerY = (int) (($size - $innerSize) / 2);

        // Cuadrado interior oscuro con bordes redondeados.
        $darkColor = imagecolorallocate(
            $image,
            self::COLOR_DARK[0],
            self::COLOR_DARK[1],
            self::COLOR_DARK[2]
        );
        $this->drawRoundedRectangle(
            $image,
            $innerX,
            $innerY,
            $innerX + $innerSize,
            $innerY + $innerSize,
            (int) ($innerSize * 0.16),
            $darkColor
        );

        // Linea horizontal decorativa cerca del centro.
        $accentColor = imagecolorallocate(
            $image,
            self::COLOR_ACCENT[0],
            self::COLOR_ACCENT[1],
            self::COLOR_ACCENT[2]
        );
        $lineY = (int) ($size * 0.5);
        $lineMargin = (int) ($size * 0.22);
        $lineThickness = max(2, (int) ($size * 0.06));
        imagefilledrectangle(
            $image,
            $lineMargin,
            $lineY - (int) ($lineThickness / 2),
            $size - $lineMargin,
            $lineY + (int) ($lineThickness / 2) + ($lineThickness % 2),
            $accentColor
        );

        // Circulo central como isotipo.
        $circleSize = (int) ($size * 0.08);
        imagefilledellipse(
            $image,
            (int) ($size / 2),
            (int) ($size * 0.7),
            $circleSize * 2,
            $circleSize * 2,
            $bgColor
        );

        $result = imagepng($image, $outputPath, 6);
        imagedestroy($image);

        return $result !== false;
    }

    /**
     * Dibuja un rectangulo con bordes redondeados rellenando
     * con el color dado. Implementacion propia porque GD no
     * tiene un helper directo (solo `imagearc`).
     *
     * @param  \GdImage  $image
     * @param  int  $x1
     * @param  int  $y1
     * @param  int  $x2
     * @param  int  $y2
     * @param  int  $radius
     * @param  int  $color
     * @return void
     */
    private function drawRoundedRectangle($image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        if ($radius <= 0) {
            imagefilledrectangle($image, $x1, $y1, $x2, $y2, $color);

            return;
        }

        // Relleno central (sin esquinas).
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);

        // Esquinas redondeadas.
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
