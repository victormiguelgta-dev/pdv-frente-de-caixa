<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Cada venda passa a registrar QUEM vendeu (o operador logado).
| Num caixa real isso é essencial: na conferência do fim do dia, cada
| operador responde pelas vendas que fez.
|
| nullable: vendas feitas antes do login existir ficam sem operador.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
