<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas da API
|--------------------------------------------------------------------------
|
| Rota = o "cardápio" da API: cada linha liga um endereço a um Controller
| (o garçom). Todas as rotas deste arquivo começam com /api automaticamente.
|
| De propósito, NÃO existe rota para editar (PUT/PATCH) ou apagar (DELETE)
| venda: regra do teste, "uma venda finalizada não deve ser alterada".
|
*/

/*
| throttle:120,1 = no máximo 120 requisições por minuto por endereço IP.
| É uma proteção básica contra abuso (alguém disparando milhares de chamadas).
| Por que 120 e não menos? A busca roda enquanto o operador digita, então um
| caixa movimentado faz muitas buscas por minuto. 120 sobra para uso normal.
*/
Route::middleware('throttle:120,1')->group(function () {
    // Buscar produtos por nome ou código: GET /api/products?search=arroz
    Route::get('/products', [ProductController::class, 'index']);

    // Histórico de vendas do dia: GET /api/sales
    Route::get('/sales', [SaleController::class, 'index']);

    // Finalizar venda: POST /api/sales
    Route::post('/sales', [SaleController::class, 'store']);

    // Comprovante: GET /api/sales/15
    // whereNumber: só aceita número no lugar de {sale} (/api/sales/abc dá 404).
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->whereNumber('sale');
});
