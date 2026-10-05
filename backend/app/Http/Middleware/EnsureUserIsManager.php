<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
| Middleware = um "porteiro" que roda ANTES do Controller.
|
| Este aqui só deixa passar o GERENTE. É usado nas rotas de cadastro de
| produtos: o operador de caixa recebe 403 (proibido), mesmo que tente chamar
| a API direto, sem passar pela tela.
|
| Esconder o botão no frontend não é segurança (qualquer um pode chamar a API
| pelo navegador). A regra de verdade tem que estar aqui, no servidor.
*/
class EnsureUserIsManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isManager()) {
            return response()->json(
                ['message' => 'Apenas o gerente pode acessar esta área.'],
                Response::HTTP_FORBIDDEN, // 403
            );
        }

        return $next($request);
    }
}
