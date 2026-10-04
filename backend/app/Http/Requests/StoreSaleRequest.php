<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
| StoreSaleRequest = o "segurança da porta" da rota POST /api/sales.
|
| Ele confere o FORMATO do que chegou ANTES de o Controller rodar. Se algo
| estiver errado, o Laravel responde sozinho com 422 e a lista de erros, e o
| Controller nem chega a ser chamado.
|
| Divisão de tarefas:
|   - Aqui: o formato está certo? (tem itens? quantidade é número inteiro >= 1?)
|   - SaleService: a regra de negócio vale? (produto ativo? tem estoque? o
|     dinheiro cobre o total?)
|
| Formato esperado (exemplo):
| {
|   "items": [ { "product_id": 3, "quantity": 2 }, { "product_id": 7, "quantity": 1 } ],
|   "payment_method": "cash",
|   "amount_received_cents": 5000
| }
|
| Repare que NÃO existe campo de preço nem de total: mesmo que o frontend
| envie, eles são ignorados, porque só os campos validados abaixo são usados.
*/
class StoreSaleRequest extends FormRequest
{
    // Quem pode fazer esta requisição. "true" = qualquer um, por enquanto
    // (sem login). Se fizermos o bônus de login, a proteção entra na rota.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Regra do teste: "uma venda tem um ou mais itens".
            // max:100 evita uma requisição gigante de propósito.
            'items' => ['required', 'array', 'min:1', 'max:100'],

            // O "*" quer dizer "cada item da lista".
            // distinct = o mesmo produto não pode vir em duas linhas. O
            // carrinho deve somar a quantidade numa linha só, o que deixa o
            // comprovante limpo e evita confusão no cálculo do estoque.
            'items.*.product_id' => ['required', 'integer', 'min:1', 'distinct'],

            // Inteiro de 1 a 999: nada de quantidade zero, negativa ou 2,5.
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],

            // Só aceita as opções do Enum: cash, debit, credit ou pix.
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],

            // Valor recebido em centavos.
            //   exclude_unless: se NÃO for dinheiro, o campo é descartado.
            //   required: se for dinheiro, é obrigatório.
            //   max: R$ 100.000,00, para barrar valores absurdos.
            // A comparação "recebido >= total" fica no SaleService, porque
            // só ele sabe o total verdadeiro.
            'amount_received_cents' => [
                'exclude_unless:payment_method,cash',
                'required',
                'integer',
                'min:0',
                'max:10000000',
            ],
        ];
    }

    // Mensagens de erro em português, que o frontend pode mostrar direto ao operador.
    public function messages(): array
    {
        return [
            'items.required' => 'Adicione pelo menos um produto ao carrinho.',
            'items.array' => 'Formato de itens inválido.',
            'items.min' => 'Adicione pelo menos um produto ao carrinho.',
            'items.max' => 'Uma venda pode ter no máximo :max itens diferentes.',
            'items.*.product_id.required' => 'Item sem produto informado.',
            'items.*.product_id.integer' => 'Produto inválido.',
            'items.*.product_id.min' => 'Produto inválido.',
            'items.*.product_id.distinct' => 'Este produto está repetido no carrinho.',
            'items.*.quantity.required' => 'Informe a quantidade.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
            'items.*.quantity.max' => 'A quantidade máxima por item é :max.',
            'payment_method.required' => 'Escolha a forma de pagamento.',
            'payment_method.enum' => 'Forma de pagamento inválida.',
            'amount_received_cents.required' => 'Informe o valor recebido em dinheiro.',
            'amount_received_cents.integer' => 'Valor recebido inválido.',
            'amount_received_cents.min' => 'Valor recebido inválido.',
            'amount_received_cents.max' => 'Valor recebido muito alto.',
        ];
    }
}
