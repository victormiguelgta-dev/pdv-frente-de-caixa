<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/*
| ProductController = o "garçom" dos produtos. Só tem a busca.
*/
class ProductController extends Controller
{
    /*
    | GET /api/products?search=arroz
    | GET /api/products?search=7891000100103   (código de barras)
    |
    | Devolve até 20 produtos DISPONÍVEIS (ativos) cujo nome contém o texto ou
    | cujo código começa com ele. Produto inativo nem aparece na busca: assim o
    | operador não consegue nem tentar colocá-lo no carrinho. (O SaleService
    | confere de novo na hora de finalizar, porque o frontend não é confiável.)
    */
    public function index(Request $request): AnonymousResourceCollection
    {
        // Validação simples aqui mesmo, porque é um campo só. Não precisa de
        // uma classe Request separada para isso.
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');

        $products = Product::active()
            // when(): só aplica o filtro SE houver texto de busca. Sem texto,
            // devolve os primeiros produtos (útil para a tela não abrir vazia).
            ->when($search !== '', function ($query) use ($search) {
                // Os parênteses do where(function...) agrupam o "OU":
                // ativo E (nome contém OU código começa com).
                // Segurança: o texto digitado vai para o banco como PARÂMETRO
                // separado (prepared statement), nunca colado dentro do SQL.
                // Isso impede SQL Injection.
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "{$search}%");
                });
            })
            ->orderBy('name')
            // Limite de 20: a tela do caixa não precisa de mais que isso, e
            // evita devolver o catálogo inteiro numa busca de uma letra.
            ->limit(20)
            ->get();

        return ProductResource::collection($products);
    }
}
