<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/*
| Testes do login e das permissões (operador × gerente).
*/
class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Login e logout
    // ------------------------------------------------------------------

    #[Test]
    public function login_com_usuario_e_senha_certos_devolve_token(): void
    {
        User::factory()->create(['username' => 'caixa', 'name' => 'Ana', 'password' => 'caixa123']);

        $this->postJson('/api/login', ['username' => 'caixa', 'password' => 'caixa123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'username', 'role', 'role_label']])
            ->assertJsonPath('user.role', 'operator')
            ->assertJsonMissingPath('user.password');
    }

    #[Test]
    public function senha_errada_e_usuario_inexistente_tem_a_mesma_mensagem(): void
    {
        User::factory()->create(['username' => 'caixa', 'password' => 'caixa123']);

        // A mesma mensagem nos dois casos: quem tenta invadir não descobre
        // quais usuários existem.
        $this->postJson('/api/login', ['username' => 'caixa', 'password' => 'errada'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.username.0', 'Usuário ou senha inválidos.');

        $this->postJson('/api/login', ['username' => 'ninguem', 'password' => 'qualquer'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.username.0', 'Usuário ou senha inválidos.');
    }

    #[Test]
    public function bloqueia_depois_de_5_tentativas_de_login_por_minuto(): void
    {
        foreach (range(1, 5) as $tentativa) {
            $this->postJson('/api/login', ['username' => 'x', 'password' => 'y'])->assertUnprocessable();
        }

        // A 6ª tentativa no mesmo minuto é barrada (proteção contra força bruta).
        $this->postJson('/api/login', ['username' => 'x', 'password' => 'y'])->assertTooManyRequests();
    }

    #[Test]
    public function token_do_login_da_acesso_e_logout_invalida_o_token(): void
    {
        User::factory()->create(['username' => 'caixa', 'password' => 'caixa123']);
        $token = $this->postJson('/api/login', ['username' => 'caixa', 'password' => 'caixa123'])->json('token');

        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('user.username', 'caixa');

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        // Depois do logout o token foi apagado do banco.
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ------------------------------------------------------------------
    // Sem login
    // ------------------------------------------------------------------

    #[Test]
    public function sem_login_nada_funciona(): void
    {
        $this->getJson('/api/products')->assertUnauthorized(); // 401
        $this->getJson('/api/sales')->assertUnauthorized();
        $this->postJson('/api/sales', [])->assertUnauthorized();
        $this->getJson('/api/catalog/products')->assertUnauthorized();
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Sessão expirada ou inválida. Faça login novamente.']);
    }

    #[Test]
    public function token_falso_e_recusado(): void
    {
        $this->withToken('1|tokenfalso123')->getJson('/api/products')->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Operador × gerente
    // ------------------------------------------------------------------

    #[Test]
    public function operador_vende_mas_nao_acessa_o_cadastro_de_produtos(): void
    {
        $this->actingAsOperator();
        $produto = Product::factory()->create();

        $this->getJson('/api/products')->assertOk();

        // 403 = proibido, mesmo chamando a API direto, sem passar pela tela.
        $this->getJson('/api/catalog/products')
            ->assertForbidden()
            ->assertJson(['message' => 'Apenas o gerente pode acessar esta área.']);
        $this->postJson('/api/catalog/products', [])->assertForbidden();
        $this->putJson("/api/catalog/products/{$produto->id}", ['price_cents' => 1])->assertForbidden();

        // O preço não mudou.
        $this->assertNotSame(1, $produto->fresh()->price_cents);
    }

    #[Test]
    public function gerente_acessa_o_cadastro_de_produtos(): void
    {
        $this->actingAsManager();

        $this->getJson('/api/catalog/products')->assertOk();
    }

    // ------------------------------------------------------------------
    // Quem vendeu
    // ------------------------------------------------------------------

    #[Test]
    public function venda_registra_o_operador_logado(): void
    {
        $operador = User::factory()->create(['name' => 'Ana (Caixa)']);
        Sanctum::actingAs($operador);
        $produto = Product::factory()->create();

        $saleId = $this->postJson('/api/sales', [
            'items' => [['product_id' => $produto->id, 'quantity' => 1]],
            'payment_method' => 'pix',
            'user_id' => 999, // tentativa de registrar no nome de outro: ignorada
        ])->assertCreated()->assertJsonPath('data.operator_name', 'Ana (Caixa)')->json('data.id');

        $this->assertDatabaseHas('sales', ['id' => $saleId, 'user_id' => $operador->id]);

        $this->getJson("/api/sales/{$saleId}")->assertJsonPath('data.operator_name', 'Ana (Caixa)');
    }
}
