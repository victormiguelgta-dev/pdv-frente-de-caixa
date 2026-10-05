<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
| Cabeçalhos de segurança em TODAS as respostas da API.
|
| São instruções para o navegador, que custam nada e fecham ataques comuns:
|   - X-Content-Type-Options: nosniff → o navegador não "adivinha" o tipo do
|     conteúdo (evita que um JSON seja interpretado como script).
|   - X-Frame-Options: DENY → a API não pode ser aberta dentro de um <iframe>
|     de outro site (proteção contra clickjacking).
|   - Referrer-Policy: no-referrer → não vaza endereços para outros sites.
|   - Cache-Control: no-store → respostas com vendas, valores e dados do
|     usuário não ficam guardadas no cache do navegador nem de proxies
|     (importante num computador de caixa compartilhado).
*/
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
