<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/*
| Seeder principal: é o que roda com "php artisan db:seed".
| Ele só chama os outros seeders, na ordem certa.
|
| O usuário de teste que vinha aqui por padrão no Laravel foi removido: por
| enquanto o sistema não tem login. Se fizermos o bônus de login, o usuário
| operador de exemplo entra aqui.
*/
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
