<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/*
| ProductCatalogController = o "garçom" do CADASTRO de produtos.
|
| Por que separado do ProductController? Porque são usos diferentes:
|   - ProductController: a busca rápida do caixa (só produtos ativos, 20).
|   - Este aqui: a tela de cadastro, que precisa ver TODOS (inclusive os
|     inativos, para poder reativar) e criar/editar.
|
| De propósito, NÃO existe rota para apagar produto: produto que saiu de
| linha é desativado. Apagar quebraria as vendas antigas que apontam para ele
| (o banco até bloqueia, por causa do restrictOnDelete).
*/
class ProductCatalogController extends Controller
{
    /*
    | GET /api/catalog/products?search=arroz
    | Lista todos os produtos (ativos e inativos), em ordem alfabética.
    */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');

        $products = Product::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "{$search}%");
                });
            })
            ->orderBy('name')
            // Limite de segurança para a tela não carregar milhares de linhas.
            // Para um catálogo grande, o próximo passo seria paginação.
            ->limit(200)
            ->get();

        return ProductResource::collection($products);
    }

    /*
    | POST /api/catalog/products
    | Cadastra um produto novo. A validação já passou no ProductRequest.
    */
    public function store(ProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return ProductResource::make($product)->response()->setStatusCode(201);
    }

    /*
    | PUT /api/catalog/products/{id}
    | Edita um produto: nome, preço, estoque, código ou ativo/inativo.
    |
    | Mudar o preço aqui NÃO altera vendas já feitas: cada item de venda
    | guardou o preço do dia (sale_items.unit_price_cents).
    */
    public function update(ProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->validated());

        return ProductResource::make($product);
    }
}
