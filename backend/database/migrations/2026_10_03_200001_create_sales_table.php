<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tabela de vendas: o "cabeçalho" do comprovante (total, pagamento, troco).
| Os produtos vendidos ficam em outra tabela, sale_items, porque uma venda
| tem VÁRIOS itens.
|
| Decisão: não existe venda "em aberto" no banco. O carrinho vive só na tela
| do operador. A venda só é gravada quando é FINALIZADA, com tudo calculado.
| Por isso não há coluna de status: toda linha desta tabela é uma venda
| finalizada, e created_at é o momento em que ela foi finalizada.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            // Forma de pagamento: 'cash', 'debit', 'credit' ou 'pix'.
            // A lista de opções válidas está no Enum App\Enums\PaymentMethod.
            $table->string('payment_method', 20);

            // Total da venda em centavos, CALCULADO PELO BACKEND (nunca vem do
            // frontend). Regra do teste: "o total precisa ser garantido pela
            // sua aplicação".
            $table->unsignedInteger('total_cents');

            // Valor que o cliente entregou em dinheiro. nullable() = pode ficar
            // vazio, porque no cartão e no pix não existe "valor recebido".
            $table->unsignedInteger('amount_received_cents')->nullable();

            // Troco = recebido - total. Fica 0 quando não é dinheiro.
            // Gravamos o troco (mesmo dando para recalcular) para o comprovante
            // mostrar exatamente o que foi devolvido no dia.
            $table->unsignedInteger('change_cents')->default(0);

            // created_at = data e hora da venda (usado no histórico do dia).
            $table->timestamps();

            // Índice para o "histórico de vendas do dia" filtrar por data rápido.
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
