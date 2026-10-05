<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/*
| Usuário do sistema: operador de caixa ou gerente.
|
| Não existe rota para criar usuário nem para mudar perfil: os usuários vêm
| do seeder (UserSeeder). Assim ninguém consegue se "promover" a gerente
| pela API.
*/
#[Fillable(['name', 'username', 'role', 'email', 'password'])]
// Hidden: a senha (mesmo criptografada) nunca sai em nenhuma resposta JSON.
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    // HasApiTokens (Sanctum): permite criar o "token" de login, a chave que o
    // frontend envia em toda requisição para provar quem está logado.
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // 'hashed': a senha é gravada criptografada (bcrypt), nunca em texto.
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }
}
