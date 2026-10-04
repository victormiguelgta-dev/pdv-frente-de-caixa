<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/*
| SaleController = o "garçom" das vendas.
| Repare como os métodos são curtos: o garçom não cozinha. A validação está
| no StoreSaleRequest e as regras estão no SaleService.
|
| De propósito, NÃO existem os métodos update (editar) e destroy (apagar):
| venda finalizada não pode ser alterada.
*/
class SaleController extends Controller
{
    /*
    | GET /api/sales
    | Histórico de vendas DO DIA (bônus), da mais recente para a mais antiga.
    */
    public function index(): AnonymousResourceCollection
    {
        $sales = Sale::query()
            ->whereDate('created_at', today())
            ->withCount('items')  // só a quantidade de itens, não a lista (mais leve)
            // Da mais nova para a mais antiga. Ordenamos pelo id (e não pela
            // hora) porque duas vendas no mesmo segundo empatariam na hora,
            // e o id sempre cresce.
            ->orderByDesc('id')
            ->get();

        return SaleResource::collection($sales);
    }

    /*
    | POST /api/sales
    | Finaliza uma venda.
    |
    | O Laravel entrega os parâmetros prontos ("injeção de dependência"):
    |   - StoreSaleRequest: quando o método começa, a validação JÁ passou.
    |   - SaleService: o Laravel cria o serviço sozinho.
    */
    public function store(StoreSaleRequest $request, SaleService $saleService): JsonResponse
    {
        // validated() devolve SÓ os campos validados. Qualquer campo extra que
        // o frontend mandar (ex: "total": 1) é jogado fora aqui.
        $data = $request->validated();

        $sale = $saleService->finalize(
            items: $data['items'],
            paymentMethod: PaymentMethod::from($data['payment_method']),
            amountReceivedCents: $data['amount_received_cents'] ?? null,
        );

        // 201 Created = o código HTTP para "criei um registro novo".
        return SaleResource::make($sale)->response()->setStatusCode(201);
    }

    /*
    | GET /api/sales/{id}
    | Comprovante de uma venda.
    |
    | "Sale $sale": o Laravel pega o {sale} da URL e busca a venda no banco
    | sozinho (route model binding). Se o id não existir, ele responde 404.
    */
    public function show(Sale $sale): SaleResource
    {
        return SaleResource::make($sale->load('items'));
    }
}
