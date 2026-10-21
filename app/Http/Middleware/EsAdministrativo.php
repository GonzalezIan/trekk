<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo al Administrativo (users.rol = 'admin').
 * Se usa en las rutas que únicamente puede usar el Administrativo (ej.: CU 1).
 */
class EsAdministrativo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esAdministrativo()) {
            abort(403, 'Solo el administrativo puede realizar esta operación.');
        }

        return $next($request);
    }
}
