<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
| SaleService = o "cozinheiro-chefe". TODAS as regras de negócio da venda
| ficam aqui, num lugar só.
|
| Por que não colocar isso direto no Controller?
|   - O Controller fica curto e só "recebe e repassa".
|   - As regras ficam fáceis de achar, de ler e de testar.
|   - Se um dia a venda vier de outro lugar (um app, uma importação), a
|     mesma regra é reaproveitada.
|
| Regras do teste garantidas aqui:
|   1. Total e subtotais confiáveis → calculados AQUI com o preço do banco.
|      O frontend só envia "qual produto" e "quantos".
|   2. Preço registrado na venda → copiado para sale_items.
|   3. Produto indisponível não entra → conferido produto a produto.
|   4. Dinheiro: recebido >= total, troco = diferença.
|   (+ bônus) Estoque: não vende mais do que tem e dá baixa ao vender.
*/
class SaleService
{
    /**
     * Finaliza uma venda e devolve a venda salva, com os itens.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $items  já validados pelo StoreSaleRequest
     * @param  int|null  $amountReceivedCents  só no dinheiro
     *
     * @throws ValidationException quando alguma regra é violada (vira resposta 422 com a mensagem)
     */
    public function finalize(array $items, PaymentMethod $paymentMethod, ?int $amountReceivedCents): Sale
    {
        /*
        | DB::transaction = "tudo ou nada". Salvar uma venda mexe em 3 tabelas
        | (sales, sale_items e o estoque em products). Se der erro no meio do
        | caminho, o banco desfaz tudo o que já tinha sido feito. Nunca fica
        | uma venda sem itens ou um estoque baixado sem venda.
        */
        return DB::transaction(function () use ($items, $paymentMethod, $amountReceivedCents) {
            /*
            | Busca TODOS os produtos da venda numa consulta só (em vez de uma
            | por item). keyBy('id') transforma a lista num "dicionário", para
            | achar cada produto rápido pelo id: $products[3].
            |
            | lockForUpdate(): "tranca" essas linhas até a venda terminar. Se
            | dois caixas venderem o último azeite ao mesmo tempo, o segundo
            | espera o primeiro terminar e então vê o estoque já atualizado.
            | (No SQLite isso é automático; no MySQL/PostgreSQL esta linha é
            | que garante.)
            */
            $productIds = array_column($items, 'product_id');
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $errors = [];
            $lines = [];
            $totalCents = 0;

            foreach ($items as $index => $item) {
                $product = $products->get($item['product_id']);
                $quantity = $item['quantity'];

                // O nome do campo com erro segue o formato da validação do
                // Laravel ("items.0.product_id"), para o frontend saber
                // exatamente QUAL item do carrinho tem problema.
                $field = "items.{$index}.product_id";

                if ($product === null) {
                    $errors[$field] = 'Produto não encontrado.';

                    continue; // pula para o próximo item
                }

                // Regra 3: produto indisponível não entra em venda nova.
                if (! $product->active) {
                    $errors[$field] = "O produto \"{$product->name}\" não está mais disponível.";

                    continue;
                }

                // Bônus estoque: não vende mais do que existe.
                if ($product->stock < $quantity) {
                    $errors[$field] = "Estoque insuficiente para \"{$product->name}\" (disponível: {$product->stock}).";

                    continue;
                }

                // Regra 1: subtotal calculado com o preço DO BANCO.
                $subtotalCents = $product->price_cents * $quantity;
                $totalCents += $subtotalCents;

                // Regra 2: "fotografia" do produto no momento da venda.
                $lines[] = [
                    'product' => $product,
                    'data' => [
                        'product_id' => $product->id,
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                        'unit_price_cents' => $product->price_cents,
                        'quantity' => $quantity,
                        'subtotal_cents' => $subtotalCents,
                    ],
                ];
            }

            /*
            | Juntamos todos os erros e devolvemos de uma vez, em vez de parar
            | no primeiro. Assim o operador vê tudo o que precisa corrigir.
            | ValidationException vira uma resposta 422 no mesmo formato dos
            | erros de validação. A transação desfaz qualquer coisa feita.
            */
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            // Regra 4: pagamento em dinheiro.
            $changeCents = 0;

            if ($paymentMethod->isCash()) {
                // O StoreSaleRequest já exige o valor no dinheiro; o "?? 0" é
                // só uma garantia caso este serviço seja chamado de outro lugar.
                $amountReceivedCents ??= 0;

                if ($amountReceivedCents < $totalCents) {
                    throw ValidationException::withMessages([
                        'amount_received_cents' => sprintf(
                            'O valor recebido (%s) é menor que o total da venda (%s).',
                            $this->formatMoney($amountReceivedCents),
                            $this->formatMoney($totalCents),
                        ),
                    ]);
                }

                $changeCents = $amountReceivedCents - $totalCents;
            } else {
                // Cartão e pix não têm "valor recebido": o que vier é descartado.
                $amountReceivedCents = null;
            }

            // Tudo certo: grava a venda...
            $sale = Sale::create([
                'payment_method' => $paymentMethod,
                'total_cents' => $totalCents,
                'amount_received_cents' => $amountReceivedCents,
                'change_cents' => $changeCents,
            ]);

            // ...os itens, e dá baixa no estoque.
            foreach ($lines as $line) {
                $sale->items()->create($line['data']);
                $line['product']->decrement('stock', $line['data']['quantity']);
            }

            // Devolve a venda já com os itens carregados (para o comprovante).
            return $sale->load('items');
        });
    }

    // 1990 → "R$ 19,90". Usado só nas mensagens de erro.
    private function formatMoney(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }
}
