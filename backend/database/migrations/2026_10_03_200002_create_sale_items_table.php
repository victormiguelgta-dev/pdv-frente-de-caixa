<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Itens da venda: cada linha é um produto dentro de uma venda.
| Ex: venda nº 15 → 2x Arroz, 1x Feijão (duas linhas nesta tabela).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            // A qual venda este item pertence (chave estrangeira → sales.id).
            // constrained() = o banco garante que a venda existe.
            // restrictOnDelete() = o banco NÃO deixa apagar uma venda que tem
            // itens. Uma camada a mais para a regra "venda finalizada não muda".
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();

            // Qual produto foi vendido (chave estrangeira → products.id).
            // restrictOnDelete() = não deixa apagar um produto que já foi vendido.
            // Produto que sai de linha é desativado (active = false), não apagado.
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            // "FOTOGRAFIA" do produto no momento da venda.
            // Regra do teste: "o preço do produto no momento da venda deve ficar
            // registrado, mesmo que o preço do produto mude depois".
            // Se amanhã o arroz passar de R$ 25 para R$ 30, este comprovante
            // continua mostrando R$ 25. Copiamos nome e código pelo mesmo motivo.
            $table->string('product_code', 50);
            $table->string('product_name', 120);
            $table->unsignedInteger('unit_price_cents');

            $table->unsignedInteger('quantity');

            // Subtotal = preço unitário × quantidade, calculado pelo backend.
            $table->unsignedInteger('subtotal_cents');

            // Sem timestamps: o item nasce junto com a venda e nunca muda.
            // A data já está em sales.created_at.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
