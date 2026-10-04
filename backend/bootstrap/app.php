<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Nas rotas /api, todo erro volta em JSON (nunca uma página HTML),
        // para o frontend sempre conseguir ler a resposta.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // 404: mensagem em português, sem expor nomes internos de classes
        // (o padrão do Laravel diria "No query results for model [App\Models\Sale]").
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Registro não encontrado.'], 404);
            }
        });

        // 429: passou do limite de requisições por minuto (throttle nas rotas).
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(
                    ['message' => 'Muitas requisições. Aguarde alguns segundos e tente de novo.'],
                    429,
                    $e->getHeaders(),
                );
            }
        });
    })->create();
