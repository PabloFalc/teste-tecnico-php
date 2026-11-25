<?php

use App\Modules\Usuarios\Http\Middleware\VerificaRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Carrega app/Modules/*/routes.php com prefixo /api e middleware api.
            foreach (glob(__DIR__.'/../app/Modules/*/routes.php') as $arquivo) {
                Route::middleware('api')->prefix('api')->group($arquivo);
            }
        },
    )
    // registra o middleware VerificaRole com o alias 'tipo' para ser usado nas rotas
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['tipo' => VerificaRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
