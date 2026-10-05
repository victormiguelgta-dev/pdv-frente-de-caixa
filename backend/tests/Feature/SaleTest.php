<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/*
| Testes automáticos das REGRAS DE NEGÓCIO da venda.
|
| Cada método com #[Test] é um teste independente que segue 3 etapas:
|   1. Prepara (Arrange): cria os produtos de que o teste precisa.
|   2. Executa (Act): chama a API como o frontend chamaria.
|   3. Confere (Assert): verifica a resposta e o que ficou no banco.
|
| Para rodar: php artisan test
|
| RefreshDatabase: cada teste começa com um banco VAZIO, em memória,
| separado do banco de desenvolvimento. Um teste nunca atrapalha o outro.
*/
class SaleTest extends TestCase
{
    use RefreshDatabase;

    // Todos os testes daqui rodam com um operador de caixa logado.
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsOperator();
    }

    // ------------------------------------------------------------------
    // Regra: "o total e os subtotais são confiáveis"
    // ------------------------------------------------------------------

    #[Test]
    public function calcula_total_e_troco_com_o_preco_do_banco(): void
    {
        $arroz = Product::factory()->create(['price_cents' => 2590]);
        $bala = Product::factory()->create(['price_cents' => 75]);

        $response = $this->postJson('/api/sales', [
            'items' => [
                ['product_id' => $arroz->id, 'quantity' => 2],
                ['product_id' => $bala->id, 'quantity' => 1],
            ],
            'payment_method' => 'cash',
            'amount_received_cents' => 6000,
        ]);

        // 2 × 25,90 + 0,75 = 52,55. Recebeu 60,00, troco 7,45.
        $response->assertCreated()
            ->assertJsonPath('data.total_cents', 5255)
            ->assertJsonPath('data.change_cents', 745)
            ->assertJsonPath('data.items.0.subtotal_cents', 5180)
            ->assertJsonPath('data.items.1.subtotal_cents', 75);
    }

    #[Test]
    public function ignora_preco_e_total_enviados_pelo_frontend(): void
    {
        $produto = Product::factory()->create(['price_cents' => 1000]);

        // Simula alguém alterando a requisição para pagar R$ 0,01.
        $response = $this->postJson('/api/sales', [
            'items' => [
                ['product_id' => $produto->id, 'quantity' => 3, 'price_cents' => 1, 'subtotal_cents' => 1],
            ],
            'payment_method' => 'pix',
            'total_cents' => 1,
        ]);

        // O total continua 3 × R$ 10,00 = R$ 30,00.
        $response->assertCreated()
            ->assertJsonPath('data.total_cents', 3000)
            ->assertJsonPath('data.items.0.unit_price_cents', 1000);

        $this->assertDatabaseHas('sales', ['total_cents' => 3000]);
    }

    // ------------------------------------------------------------------
    // Regra: "o preço no momento da venda fica registrado"
    // ------------------------------------------------------------------

    #[Test]
    public function preco_da_venda_nao_muda_quando_o_produto_muda_de_preco(): void
    {
        $produto = Product::factory()->create(['name' => 'Café 500g', 'price_cents' => 1890]);

        $saleId = $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => 'debit',
        ])->json('data.id');

        // Depois da venda, o café fica mais caro e muda de nome.
        $produto->update(['price_cents' => 2500, 'name' => 'Café Premium 500g']);

        // O comprovante continua com o preço e o nome do dia da venda.
        $this->getJson("/api/sales/{$saleId}")
            ->assertOk()
            ->assertJsonPath('data.items.0.unit_price_cents', 1890)
            ->assertJsonPath('data.items.0.product_name', 'Café 500g')
            ->assertJsonPath('data.total_cents', 1890);
    }

    // ------------------------------------------------------------------
    // Regra: "uma venda tem um ou mais itens"
    // ------------------------------------------------------------------

    #[Test]
    public function nao_aceita_venda_sem_itens(): void
    {
        $this->postJson('/api/sales', ['items' => [], 'payment_method' => 'pix'])
            ->assertUnprocessable() // 422
            ->assertJsonValidationErrors(['items']);

        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function nao_aceita_quantidade_zero_negativa_ou_quebrada(): void
    {
        $produto = Product::factory()->create();

        foreach ([0, -1, 2.5] as $quantidade) {
            $this->postJson('/api/sales', [
                'items' => [['product_id' => $produto->id, 'quantity' => $quantidade]],
                'payment_method' => 'pix',
            ])->assertJsonValidationErrors(['items.0.quantity']);
        }

        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function nao_aceita_forma_de_pagamento_invalida(): void
    {
        $produto = Product::factory()->create();

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => 'cheque',
        ])->assertJsonValidationErrors(['payment_method']);
    }

    // ------------------------------------------------------------------
    // Regra: "no dinheiro, recebido não pode ser menor que o total"
    // ------------------------------------------------------------------

    #[Test]
    public function recusa_dinheiro_menor_que_o_total(): void
    {
        $produto = Product::factory()->create(['price_cents' => 2590, 'stock' => 10]);

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_received_cents' => 2589, // falta 1 centavo
        ])->assertJsonValidationErrors(['amount_received_cents']);

        // Nada foi gravado e o estoque não foi mexido.
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(10, $produto->fresh()->stock);
    }

    #[Test]
    public function aceita_dinheiro_exato_com_troco_zero(): void
    {
        $produto = Product::factory()->create(['price_cents' => 2590]);

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_received_cents' => 2590,
        ])->assertCreated()->assertJsonPath('data.change_cents', 0);
    }

    #[Test]
    public function exige_valor_recebido_no_dinheiro(): void
    {
        $produto = Product::factory()->create();

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => 'cash',
        ])->assertJsonValidationErrors(['amount_received_cents']);
    }

    #[Test]
    public function cartao_e_pix_nao_tem_valor_recebido_nem_troco(): void
    {
        $produto = Product::factory()->create(['price_cents' => 1000]);

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => 'credit',
            'amount_received_cents' => 5000, // deve ser ignorado
        ])->assertCreated()
            ->assertJsonPath('data.amount_received_cents', null)
            ->assertJsonPath('data.change_cents', 0);
    }

    // ------------------------------------------------------------------
    // Regra: "produto indisponível não entra em nova venda"
    // ------------------------------------------------------------------

    #[Test]
    public function recusa_produto_inativo(): void
    {
        $ativo = Product::factory()->create();
        $inativo = Product::factory()->inactive()->create();

        $this->postJson('/api/sales', [
            'items' => [
                ['product_id' => $ativo->id, 'quantity' => 1],
                ['product_id' => $inativo->id, 'quantity' => 1],
            ],
            'payment_method' => 'pix',
        ])->assertUnprocessable()
            // O erro aponta exatamente o segundo item (índice 1).
            ->assertJsonValidationErrors(['items.1.product_id']);

        // A venda inteira é recusada: nem o produto ativo foi vendido.
        $this->assertDatabaseCount('sales', 0);
    }

    #[Test]
    public function recusa_produto_que_nao_existe(): void
    {
        $this->postJson('/api/sales', [
            'items' => [['product_id' => 9999, 'quantity' => 1]],
            'payment_method' => 'pix',
        ])->assertJsonValidationErrors(['items.0.product_id']);
    }

    // ------------------------------------------------------------------
    // Bônus: estoque
    // ------------------------------------------------------------------

    #[Test]
    public function da_baixa_no_estoque_ao_vender(): void
    {
        $produto = Product::factory()->create(['stock' => 10]);

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 3]],
            'payment_method' => 'pix',
        ])->assertCreated();

        $this->assertSame(7, $produto->fresh()->stock);
    }

    #[Test]
    public function recusa_quantidade_maior_que_o_estoque(): void
    {
        $produto = Product::factory()->create(['stock' => 2]);

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 3]],
            'payment_method' => 'pix',
        ])->assertJsonValidationErrors(['items.0.product_id']);

        $this->assertSame(2, $produto->fresh()->stock);
    }

    // ------------------------------------------------------------------
    // Regra: "uma venda finalizada não deve ser alterada"
    // ------------------------------------------------------------------

    #[Test]
    public function api_nao_tem_rota_para_editar_ou_apagar_venda(): void
    {
        $sale = $this->criarVenda();

        // 405 = "método não permitido": a rota simplesmente não existe.
        $this->putJson("/api/sales/{$sale->id}", ['total_cents' => 1])->assertMethodNotAllowed();
        $this->patchJson("/api/sales/{$sale->id}", ['total_cents' => 1])->assertMethodNotAllowed();
        $this->deleteJson("/api/sales/{$sale->id}")->assertMethodNotAllowed();

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'total_cents' => $sale->total_cents]);
    }

    #[Test]
    public function model_bloqueia_alterar_venda_finalizada(): void
    {
        $sale = $this->criarVenda();

        // Segunda linha de defesa: mesmo pelo código, alterar dá erro.
        $this->expectException(LogicException::class);
        $sale->update(['total_cents' => 1]);
    }

    #[Test]
    public function model_bloqueia_alterar_item_de_venda_finalizada(): void
    {
        $sale = $this->criarVenda();

        $this->expectException(LogicException::class);
        $sale->items->first()->update(['quantity' => 99]);
    }

    // ------------------------------------------------------------------
    // Comprovante e histórico
    // ------------------------------------------------------------------

    #[Test]
    public function mostra_o_comprovante_da_venda(): void
    {
        $sale = $this->criarVenda();

        $this->getJson("/api/sales/{$sale->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $sale->id)
            ->assertJsonPath('data.payment_method_label', 'Pix')
            ->assertJsonCount(1, 'data.items');
    }

    #[Test]
    public function venda_inexistente_responde_404(): void
    {
        $this->getJson('/api/sales/9999')
            ->assertNotFound()
            ->assertJson(['message' => 'Registro não encontrado.']);
    }

    #[Test]
    public function historico_mostra_so_as_vendas_de_hoje(): void
    {
        // travelTo "viaja no tempo": cria uma venda ontem e outra hoje.
        $this->travelTo(now()->subDay());
        $this->criarVenda();
        $this->travelBack();

        $deHoje = $this->criarVenda();

        $this->getJson('/api/sales')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $deHoje->id)
            ->assertJsonPath('data.0.items_count', 1);
    }

    // Atalho usado por vários testes: cria uma venda simples pela API.
    private function criarVenda(): Sale
    {
        $produto = Product::factory()->create(['price_cents' => 1000]);

        $id = $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => PaymentMethod::Pix->value,
        ])->assertCreated()->json('data.id');

        return Sale::with('items')->findOrFail($id);
    }
}
