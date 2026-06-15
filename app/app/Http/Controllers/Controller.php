<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Controlador base de la aplicacion.
 *
 * Incluye los traits `AuthorizesRequests` y `ValidatesRequests` que
 * exponen los helpers `$this->authorize()` y `$this->validate()` en
 * todos los controladores que extiendan de esta clase. Esto se hacia
 * automaticamente en versiones anteriores de Laravel a traves de
 * `App\Http\Controllers\Controller`, pero Laravel 13 lo dejo vacio.
 */
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;
}
