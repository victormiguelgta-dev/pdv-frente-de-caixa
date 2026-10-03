<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/*
| Seeder = "semeador": coloca dados de exemplo no banco.
| O teste pede "massa de dados de exemplo (produtos) para testar sem digitar
| tudo na mão". Para rodar: php artisan db:seed (ou migrate --seed).
|
| Os produtos imitam um mercadinho, com códigos no formato do código de
| barras brasileiro (começam com 789). Alguns casos especiais foram
| colocados de propósito para testar as regras na tela:
|   - 2 produtos INATIVOS  → não podem aparecer na busca nem entrar em venda
|   - 1 produto SEM ESTOQUE → para testar o bônus de estoque
|   - preços "quebrados" (R$ 4,99, R$ 0,75) → para testar a conta de centavos
*/
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Cada linha: [código, nome, preço em centavos, estoque, ativo?]
        // Lembrete: 2590 centavos = R$ 25,90.
        $products = [
            ['7891000100103', 'Arroz Branco Tipo 1 5kg', 2590, 40, true],
            ['7891000100110', 'Feijão Carioca 1kg', 849, 60, true],
            ['7891000100127', 'Açúcar Refinado 1kg', 499, 50, true],
            ['7891000100134', 'Café Torrado e Moído 500g', 1890, 35, true],
            ['7891000100141', 'Óleo de Soja 900ml', 799, 45, true],
            ['7891000100158', 'Macarrão Espaguete 500g', 459, 70, true],
            ['7891000100165', 'Molho de Tomate 340g', 289, 80, true],
            ['7891000100172', 'Farinha de Trigo 1kg', 599, 30, true],
            ['7891000100189', 'Sal Refinado 1kg', 249, 40, true],
            ['7891000100196', 'Leite Integral 1L', 549, 120, true],
            ['7891000100202', 'Manteiga com Sal 200g', 1290, 25, true],
            ['7891000100219', 'Queijo Muçarela Fatiado 150g', 899, 20, true],
            ['7891000100226', 'Presunto Fatiado 200g', 749, 20, true],
            ['7891000100233', 'Iogurte Natural 170g', 349, 50, true],
            ['7891000100240', 'Ovos Brancos (dúzia)', 1199, 30, true],
            ['7891000100257', 'Pão de Forma Tradicional 500g', 899, 25, true],
            ['7891000100264', 'Biscoito Recheado Chocolate 130g', 349, 60, true],
            ['7891000100271', 'Bala de Hortelã (unidade)', 75, 500, true],
            ['7891000100288', 'Refrigerante Cola 2L', 999, 48, true],
            ['7891000100295', 'Suco de Laranja Integral 1L', 1149, 24, true],
            ['7891000100301', 'Água Mineral sem Gás 500ml', 250, 100, true],
            ['7891000100318', 'Cerveja Lata 350ml', 429, 96, true],
            ['7891000100325', 'Sabão em Pó 1,6kg', 2190, 18, true],
            ['7891000100332', 'Detergente Líquido 500ml', 289, 60, true],
            ['7891000100349', 'Papel Higiênico Folha Dupla (12 rolos)', 2299, 22, true],
            ['7891000100356', 'Creme Dental 90g', 499, 40, true],
            ['7891000100363', 'Sabonete em Barra 85g', 279, 70, true],
            ['7891000100370', 'Shampoo 350ml', 1599, 15, true],

            // Caso especial: sem estoque (aparece na busca, mas não pode ser vendido).
            ['7891000100387', 'Azeite de Oliva Extra Virgem 500ml', 3990, 0, true],

            // Casos especiais: INATIVOS (saíram de linha).
            ['7891000100394', 'Achocolatado em Pó 400g (descontinuado)', 899, 12, false],
            ['7891000100400', 'Margarina 500g (descontinuado)', 799, 8, false],
        ];

        foreach ($products as [$code, $name, $priceCents, $stock, $active]) {
            /*
            | updateOrCreate: procura pelo código. Se já existe, atualiza; se
            | não existe, cria. Assim rodar o seeder duas vezes NÃO duplica os
            | produtos (o banco recusaria, porque o código é único).
            */
            Product::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'price_cents' => $priceCents,
                    'stock' => $stock,
                    'active' => $active,
                ],
            );
        }
    }
}
