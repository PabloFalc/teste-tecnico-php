<?php

namespace App\Modules\Usuarios\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Uso: ->middleware(['auth:sanctum', 'tipo:recrutador'])
class VerificaRole
{
    public function handle(Request $request, Closure $next, string ...$tipos): Response
    {
        if (! in_array($request->user()?->tipo, $tipos, true)) {
            return response()->json(['message' => 'Sem permissão.'], 403);
        }

        return $next($request);
    }
}
