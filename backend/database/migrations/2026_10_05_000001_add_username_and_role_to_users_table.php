<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Acrescenta à tabela "users" (que já vem com o Laravel) o que o PDV precisa:
|   - username: o login curto que o operador digita (ex: "caixa"). Em caixa
|     de loja é mais rápido que digitar um e-mail inteiro.
|   - role: o perfil (operator ou manager), que define o que cada um pode fazer.
| O e-mail passa a ser opcional, porque o login é pelo username.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // unique: dois usuários não podem ter o mesmo login.
            $table->string('username', 50)->unique()->after('name');
            // Padrão "operator": quem for criado sem perfil tem o MENOR acesso.
            $table->string('role', 20)->default('operator')->after('username');
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role']);
        });
    }
};
