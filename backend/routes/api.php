<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da API
|--------------------------------------------------------------------------
|
| Rota = o "cardápio" da API: cada linha liga um endereço a um Controller
| (o garçom). Todas as rotas deste arquivo começam com /api automaticamente.
|
| Rotas que vamos criar nos próximos passos:
|
|   GET  /api/products?search=arroz   buscar produtos por nome ou código
|   POST /api/sales                   finalizar uma venda
|   GET  /api/sales/{id}              ver o comprovante de uma venda
|   GET  /api/sales                   histórico de vendas do dia (bônus)
|
| De propósito, NÃO existe rota para editar (PUT/PATCH) ou apagar (DELETE)
| venda: regra do teste, "uma venda finalizada não deve ser alterada".
|
*/

// Rota de teste para conferir se a API responde. Sai no passo 4.
Route::get('/ping', fn () => response()->json(['status' => 'ok']));
