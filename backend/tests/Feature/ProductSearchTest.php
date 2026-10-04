<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/*
| Testes da busca de produtos (GET /api/products?search=...).
*/
class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function busca_por_parte_do_nome(): void
    {
        Product::factory()->create(['name' => 'Arroz Branco 5kg']);
        Product::factory()->create(['name' => 'Feijão Carioca 1kg']);

        $this->getJson('/api/products?search=arroz')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Arroz Branco 5kg');
    }

    #[Test]
    public function busca_pelo_codigo(): void
    {
        Product::factory()->create(['code' => '7891000100103', 'name' => 'Arroz']);
        Product::factory()->create(['code' => '7899999999999', 'name' => 'Outro']);

        $this->getJson('/api/products?search=7891000100103')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', '7891000100103');
    }

    #[Test]
    public function produto_inativo_nao_aparece_na_busca(): void
    {
        Product::factory()->create(['name' => 'Margarina nova']);
        Product::factory()->inactive()->create(['name' => 'Margarina antiga']);

        $this->getJson('/api/products?search=margarina')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Margarina nova');
    }

    #[Test]
    public function devolve_no_maximo_20_produtos(): void
    {
        Product::factory()->count(25)->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(20, 'data');
    }

    #[Test]
    public function devolve_so_os_campos_publicos(): void
    {
        Product::factory()->create();

        // O Resource define o formato. Campos internos (active, datas) não saem.
        $this->getJson('/api/products')
            ->assertOk()
            ->assertExactJsonStructure([
                'data' => [['id', 'code', 'name', 'price_cents', 'stock']],
            ]);
    }

    #[Test]
    public function recusa_busca_longa_demais(): void
    {
        $this->getJson('/api/products?search='.str_repeat('a', 101))
            ->assertJsonValidationErrors(['search']);
    }
}
