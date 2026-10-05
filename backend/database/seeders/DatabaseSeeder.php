<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/*
| Seeder principal: é o que roda com "php artisan db:seed".
| Ele só chama os outros seeders, na ordem certa.
|
| Usuários (operador e gerente) e produtos de exemplo.
*/
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
