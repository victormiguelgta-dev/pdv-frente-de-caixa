<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
| Factory = "fábrica" de produtos falsos, usada nos TESTES AUTOMÁTICOS (passo 6).
|
| Qual a diferença para o seeder?
|   - Seeder: dados fixos e realistas, para uma pessoa testar na tela.
|   - Factory: dados aleatórios, criados na hora por cada teste. Ex:
|       Product::factory()->create(['price_cents' => 1000]);
|       Product::factory()->inactive()->create();
|
| @extends Factory<Product>
*/
class ProductFactory extends Factory
{
    // Valores padrão de um produto qualquer: ativo, com estoque, preço aleatório.
    public function definition(): array
    {
        return [
            // 13 dígitos começando com 789, sem repetir (unique).
            'code' => fake()->unique()->numerify('789##########'),
            'name' => ucfirst(fake()->words(3, true)),
            'price_cents' => fake()->numberBetween(100, 10000), // R$ 1,00 a R$ 100,00
            'active' => true,
            'stock' => 100,
        ];
    }

    // Variação: produto que saiu de linha. Uso: Product::factory()->inactive()
    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }

    // Variação: produto sem estoque. Uso: Product::factory()->outOfStock()
    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
