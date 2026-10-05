<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductCatalogController;
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
| Três níveis de acesso:
|   1. Público: só o login.
|   2. Logado (operador ou gerente): vender e consultar vendas.
|   3. Só gerente: cadastro de produtos.
|
| De propósito, NÃO existe rota para editar (PUT/PATCH) ou apagar (DELETE)
| venda: regra do teste, "uma venda finalizada não deve ser alterada".
|
*/

/*
| Login: público, mas com limite de 5 tentativas por minuto por IP.
| Isso impede alguém de testar milhares de senhas seguidas ("força bruta").
*/
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

/*
| auth:sanctum = só passa quem enviar um token de login válido; sem token,
| a resposta é 401 (não autenticado).
|
| throttle:120,1 = no máximo 120 requisições por minuto por endereço IP.
| É uma proteção básica contra abuso. Por que 120 e não menos? A busca roda
| enquanto o operador digita, então um caixa movimentado faz muitas buscas.
*/
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    // Quem está logado / sair.
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Buscar produtos por nome ou código: GET /api/products?search=arroz
    Route::get('/products', [ProductController::class, 'index']);

    // Histórico de vendas do dia: GET /api/sales
    Route::get('/sales', [SaleController::class, 'index']);

    // Finalizar venda: POST /api/sales
    Route::post('/sales', [SaleController::class, 'store']);

    // Comprovante: GET /api/sales/15
    // whereNumber: só aceita número no lugar de {sale} (/api/sales/abc dá 404).
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->whereNumber('sale');

    /*
    | Cadastro de produtos: SÓ GERENTE (middleware "manager", ver
    | app/Http/Middleware/EnsureUserIsManager.php). Operador recebe 403.
    | Sem rota de apagar: produto que sai de linha é desativado, para não
    | quebrar o histórico de vendas.
    */
    Route::middleware('manager')->group(function () {
        Route::get('/catalog/products', [ProductCatalogController::class, 'index']);
        Route::post('/catalog/products', [ProductCatalogController::class, 'store']);
        Route::put('/catalog/products/{product}', [ProductCatalogController::class, 'update'])->whereNumber('product');
    });
});
