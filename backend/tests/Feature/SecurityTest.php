<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/*
| Testes das proteções básicas de segurança da API.
*/
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function respostas_da_api_tem_cabecalhos_de_seguranca(): void
    {
        $this->actingAsOperator();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    #[Test]
    public function respostas_de_erro_tambem_tem_cabecalhos_de_seguranca(): void
    {
        // Sem login: 401, e mesmo assim com os cabeçalhos.
        $this->getJson('/api/products')
            ->assertUnauthorized()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    #[Test]
    public function erros_nao_mostram_detalhes_internos_em_producao(): void
    {
        $this->actingAsOperator();
        config(['app.debug' => false]);

        // Uma rota que não existe não pode devolver nome de arquivo, linha ou classe.
        $response = $this->getJson('/api/rota-que-nao-existe')->assertNotFound();

        $this->assertArrayNotHasKey('exception', $response->json());
        $this->assertArrayNotHasKey('file', $response->json());
        $this->assertArrayNotHasKey('trace', $response->json());
    }

    #[Test]
    public function usuario_nao_ve_senha_nem_token_de_ninguem(): void
    {
        $operador = $this->actingAsOperator();

        $json = json_encode($this->getJson('/api/me')->json());

        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString($operador->password, $json);
    }
}
