<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    /*
    | Atalhos para os testes "entrarem" no sistema sem passar pela tela de login.
    | Sanctum::actingAs faz a requisição se comportar como se tivesse um token
    | válido daquele usuário.
    */
    protected function actingAsOperator(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsManager(): User
    {
        $user = User::factory()->manager()->create();
        Sanctum::actingAs($user);

        return $user;
    }
}
