<?php

use Illuminate\Support\Facades\Route;

/*
| Este backend é só uma API: quem desenha as telas é o React.
| Por isso não existe nenhuma página aqui. Esta rota só avisa, para quem abrir
| http://localhost:8000 no navegador, que a API está no ar e onde ela fica.
*/
Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'api' => url('/api'),
]));
