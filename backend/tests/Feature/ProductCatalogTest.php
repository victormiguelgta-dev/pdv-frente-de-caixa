<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/*
| Testes do cadastro de produtos (/api/catalog/products).
*/
class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    // Dados de um produto válido, reaproveitados nos testes.
    private function validProduct(array $overrides = []): array
    {
        return array_merge([
            'code' => '7890000000001',
            'name' => 'Farinha de Mandioca 1kg',
            'price_cents' => 799,
            'stock' => 20,
            'active' => true,
        ], $overrides);
    }

    #[Test]
    public function cadastra_um_produto_novo(): void
    {
        $this->postJson('/api/catalog/products', $this->validProduct())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Farinha de Mandioca 1kg')
            ->assertJsonPath('data.price_cents', 799);

        $this->assertDatabaseHas('products', ['code' => '7890000000001', 'stock' => 20]);
    }

    #[Test]
    public function produto_cadastrado_ja_aparece_na_busca_do_caixa(): void
    {
        $this->postJson('/api/catalog/products', $this->validProduct())->assertCreated();

        $this->getJson('/api/products?search=mandioca')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function nao_aceita_codigo_repetido(): void
    {
        Product::factory()->create(['code' => '7890000000001']);

        $this->postJson('/api/catalog/products', $this->validProduct())
            ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function nao_aceita_preco_zero_ou_estoque_negativo(): void
    {
        $this->postJson('/api/catalog/products', $this->validProduct(['price_cents' => 0, 'stock' => -1]))
            ->assertJsonValidationErrors(['price_cents', 'stock']);

        $this->assertDatabaseCount('products', 0);
    }

    #[Test]
    public function exige_os_campos_obrigatorios(): void
    {
        $this->postJson('/api/catalog/products', [])
            ->assertJsonValidationErrors(['code', 'name', 'price_cents', 'stock', 'active']);
    }

    #[Test]
    public function edita_preco_e_estoque(): void
    {
        $produto = Product::factory()->create(['price_cents' => 1000, 'stock' => 5]);

        $this->putJson("/api/catalog/products/{$produto->id}", $this->validProduct([
            'code' => $produto->code, // o próprio código não conta como repetido
            'price_cents' => 1250,
            'stock' => 30,
        ]))->assertOk()->assertJsonPath('data.price_cents', 1250);

        $this->assertSame(30, $produto->fresh()->stock);
    }

    #[Test]
    public function mudar_o_preco_nao_altera_vendas_antigas(): void
    {
        $produto = Product::factory()->create(['price_cents' => 1000]);

        $saleId = $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 2]],
            'payment_method' => 'pix',
        ])->json('data.id');

        $this->putJson("/api/catalog/products/{$produto->id}", $this->validProduct([
            'code' => $produto->code,
            'price_cents' => 9999,
        ]))->assertOk();

        // O comprovante continua com o preço do dia da venda.
        $this->getJson("/api/sales/{$saleId}")
            ->assertJsonPath('data.items.0.unit_price_cents', 1000)
            ->assertJsonPath('data.total_cents', 2000);
    }

    #[Test]
    public function produto_desativado_some_do_caixa_mas_continua_no_cadastro(): void
    {
        $produto = Product::factory()->create(['name' => 'Margarina 500g']);

        $this->putJson("/api/catalog/products/{$produto->id}", $this->validProduct([
            'code' => $produto->code,
            'name' => 'Margarina 500g',
            'active' => false,
        ]))->assertOk();

        // No caixa não aparece e não pode ser vendido...
        $this->getJson('/api/products?search=margarina')->assertJsonCount(0, 'data');
        $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => 'pix',
        ])->assertJsonValidationErrors(['items.0.product_id']);

        // ...mas no cadastro aparece, para poder ser reativado.
        $this->getJson('/api/catalog/products?search=margarina')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.active', false);
    }

    #[Test]
    public function nao_existe_rota_para_apagar_produto(): void
    {
        $produto = Product::factory()->create();

        $this->deleteJson("/api/catalog/products/{$produto->id}")->assertMethodNotAllowed();
        $this->assertDatabaseHas('products', ['id' => $produto->id]);
    }

    #[Test]
    public function editar_produto_inexistente_responde_404(): void
    {
        $this->putJson('/api/catalog/products/9999', $this->validProduct())->assertNotFound();
    }
}
