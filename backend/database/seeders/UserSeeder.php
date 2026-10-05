<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/*
| Usuários de exemplo, para quem avalia conseguir entrar no sistema.
| As senhas estão no README.
|
| ATENÇÃO: são senhas de DEMONSTRAÇÃO. Num sistema real, cada pessoa teria a
| própria senha, criada por ela, e este seeder não rodaria em produção.
*/
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['username' => 'caixa', 'name' => 'Ana (Caixa)', 'role' => UserRole::Operator, 'password' => 'caixa123'],
            ['username' => 'gerente', 'name' => 'Carlos (Gerente)', 'role' => UserRole::Manager, 'password' => 'gerente123'],
        ];

        foreach ($users as $user) {
            // updateOrCreate pelo username: rodar de novo não duplica.
            // A senha é criptografada automaticamente (cast 'hashed' no Model).
            User::updateOrCreate(
                ['username' => $user['username']],
                ['name' => $user['name'], 'role' => $user['role'], 'password' => $user['password']],
            );
        }
    }
}
