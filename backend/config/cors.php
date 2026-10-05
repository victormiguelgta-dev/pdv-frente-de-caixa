<?php

/*
|--------------------------------------------------------------------------
| CORS (Cross-Origin Resource Sharing)
|--------------------------------------------------------------------------
|
| O que é: o navegador, por segurança, bloqueia uma página de um endereço
| (ex: http://localhost:5173, nosso React) de chamar outro endereço
| (ex: http://localhost:8000, nossa API). O CORS é a "lista de convidados"
| que diz quais endereços PODEM chamar a API.
|
| Por que não usar '*' (todo mundo)? Porque aí qualquer site da internet
| poderia chamar a nossa API a partir do navegador de quem estivesse logado.
| Liberamos só o endereço do frontend, que vem do arquivo .env.
|
*/

return [

    // Só as rotas da API passam pelo CORS.
    'paths' => ['api/*'],

    // Métodos HTTP que o front usa: GET (buscar), POST (criar), PUT (editar
    // produto no cadastro) e OPTIONS (o navegador manda antes, perguntando
    // "posso?"). DELETE não é liberado: nada é apagado no sistema.
    // (Venda continua sem rota de editar: liberar o PUT no CORS não cria rota.)
    'allowed_methods' => ['GET', 'POST', 'PUT', 'OPTIONS'],

    // Único endereço autorizado. Configurado em FRONTEND_URL no .env.
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    // Cabeçalhos que o front pode enviar. "Authorization" leva o token de
    // login (bônus), os outros dois dizem que estamos falando em JSON.
    'allowed_headers' => ['Content-Type', 'Accept', 'Authorization'],

    'exposed_headers' => [],

    // Por quanto tempo (segundos) o navegador pode lembrar a resposta do
    // "posso?" sem perguntar de novo. 1 hora economiza requisições.
    'max_age' => 3600,

    // Falso porque o login vai usar token no cabeçalho, não cookie.
    'supports_credentials' => false,

];
