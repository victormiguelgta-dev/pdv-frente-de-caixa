<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
| ProductRequest = o "segurança da porta" do cadastro de produtos.
| Usado tanto para CRIAR (POST) quanto para EDITAR (PUT) um produto: as
| regras são as mesmas, só muda que, ao editar, o código do próprio produto
| não conta como "repetido".
|
| Exemplo do que chega:
| { "code": "7891000100103", "name": "Arroz 5kg", "price_cents": 2590, "stock": 40, "active": true }
*/
class ProductRequest extends FormRequest
{
    // Sem login por enquanto: qualquer um pode cadastrar. Num sistema real,
    // aqui entraria "só gerente" (decisão registrada no README).
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Código: obrigatório, único e só com letras, números e hífen
            // (formato de código de barras ou código interno).
            // ignore(...): ao editar, o próprio produto não conta como duplicado.
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9-]+$/',
                Rule::unique('products', 'code')->ignore($this->route('product')),
            ],
            'name' => ['required', 'string', 'min:2', 'max:120'],

            // Preço em centavos: de R$ 0,01 a R$ 100.000,00. Produto de preço
            // zero não faz sentido num caixa e quase sempre é erro de digitação.
            'price_cents' => ['required', 'integer', 'min:1', 'max:10000000'],

            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],

            // Ativo = disponível para venda. Desativar é o jeito de "tirar de
            // linha" sem apagar (apagar quebraria o histórico das vendas).
            'active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código do produto.',
            'code.max' => 'O código pode ter no máximo :max caracteres.',
            'code.regex' => 'Use só letras, números e hífen no código.',
            'code.unique' => 'Já existe um produto com este código.',
            'name.required' => 'Informe o nome do produto.',
            'name.min' => 'O nome precisa ter pelo menos :min letras.',
            'name.max' => 'O nome pode ter no máximo :max caracteres.',
            'price_cents.required' => 'Informe o preço.',
            'price_cents.integer' => 'Preço inválido.',
            'price_cents.min' => 'O preço precisa ser maior que zero.',
            'price_cents.max' => 'Preço muito alto.',
            'stock.required' => 'Informe o estoque.',
            'stock.integer' => 'O estoque deve ser um número inteiro.',
            'stock.min' => 'O estoque não pode ser negativo.',
            'stock.max' => 'Estoque muito alto.',
            'active.required' => 'Informe se o produto está ativo.',
            'active.boolean' => 'Valor inválido para "ativo".',
        ];
    }
}
