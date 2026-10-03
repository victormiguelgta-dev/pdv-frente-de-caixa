<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Migration = a "planta" de uma tabela. Em vez de criar a tabela clicando num
| programa de banco, ela é descrita em código. Assim qualquer pessoa que baixar
| o projeto roda "php artisan migrate" e fica com o banco igualzinho.
|
| up()   = o que fazer ao rodar a migration (criar a tabela)
| down() = como desfazer (apagar a tabela), usado no "migrate:rollback"
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // Número de identificação automático: 1, 2, 3...
            $table->id();

            // Código do produto (o código de barras ou o código interno que o
            // operador digita). unique() = o banco não deixa dois produtos com
            // o mesmo código, porque a busca por código precisa achar UM só.
            $table->string('code', 50)->unique();

            // index() deixa a busca por nome mais rápida, como o índice de um
            // livro: o banco não precisa ler a tabela inteira para achar "arroz".
            $table->string('name', 120)->index();

            // Preço em CENTAVOS, como número inteiro: R$ 19,90 é gravado como 1990.
            // Por quê? Computador erra conta com decimal (0.1 + 0.2 dá
            // 0.30000000000000004). Com inteiros a conta é sempre exata.
            // unsigned = não aceita número negativo.
            $table->unsignedInteger('price_cents');

            // Disponível para venda? Produto que saiu de linha fica "false".
            // Regra do teste: "produtos que não estão mais disponíveis não
            // devem entrar em novas vendas". Por que não apagar o produto?
            // Porque vendas antigas apontam para ele, e o histórico se perderia.
            $table->boolean('active')->default(true);

            // Quantidade em estoque (bônus "cuidados com estoque").
            $table->unsignedInteger('stock')->default(0);

            // Cria created_at e updated_at, preenchidos sozinhos pelo Laravel.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
