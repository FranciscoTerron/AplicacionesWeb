<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Uso: ->middleware('role:admin,editor')
     *
     * @param  Closure(Request): (\Illuminate\Http\Response|RedirectResponse)  $next
     * @return \Illuminate\Http\Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $authUser = Auth::user();

        if (! $authUser || ! in_array($authUser->role, $roles, true)) {
            return Response::view('components.unauthorized', [
                'title' => 'Acceso Denegado',
                'subtitle' => 'No tienes permisos para acceder a esta sección',
                'message' => 'Tu cuenta no tiene los permisos necesarios para acceder a esta sección.',
                'contactMessage' => 'Por favor, contacta a un administrador del sistema para solicitar permisos de acceso.',
            ], 403);
        }

        return $next($request);
    }
}
