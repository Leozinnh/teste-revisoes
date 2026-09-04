<?php

namespace Tests\Feature;

use App\Models\Pessoa;
use App\Models\Revisao;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * Testes do painel de manutenção (apagar e popular o banco).
 * Rode com: php artisan test --filter=ManutencaoApiTest
 */
class ManutencaoApiTest extends TestCase
{
    // DatabaseMigrations (e não RefreshDatabase): o teste de sucesso
    // derruba e recria as tabelas dentro da requisição, o que não
    // combina com o rollback por transação do RefreshDatabase
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        config(['manutencao.token' => 'token-de-teste']);
    }

    public function test_recurso_desligado_sem_token_configurado(): void
    {
        config(['manutencao.token' => null]);

        $this->getJson('/api/manutencao')
            ->assertStatus(200)
            ->assertJsonPath('token_configurado', false);

        // Sem token configurado ninguém apaga nada, nem com token no corpo
        $this->postJson('/api/manutencao/limpar', ['token' => 'qualquer'])
            ->assertStatus(403);
    }

    public function test_nao_limpa_com_token_errado(): void
    {
        $pessoa = Pessoa::create([
            'nome' => 'João Santos',
            'cpf' => '39053344705', // CPF com dígitos verificadores válidos
            'sexo' => 'M',
            'data_nascimento' => '1985-03-20',
            'telefone' => '21988887777',
            'email' => 'joao@exemplo.com',
        ]);

        $this->getJson('/api/manutencao')
            ->assertJsonPath('token_configurado', true);

        $this->postJson('/api/manutencao/limpar', ['token' => 'errado'])
            ->assertStatus(403);

        // Os dados continuam intactos
        $this->assertDatabaseHas('pessoas', ['id' => $pessoa->id]);
    }

    public function test_limpa_e_popula_com_token_correto(): void
    {
        $resposta = $this->postJson('/api/manutencao/limpar', ['token' => 'token-de-teste']);

        $resposta->assertStatus(200)
            ->assertJsonPath('success', true)
            // DatabaseSeeder cria 20 pessoas
            ->assertJsonPath('contagem.pessoas', 20);

        // Dados novos com as placas limpas: nada de '#' literal (bug do
        // seeder antigo), hífen ou minúsculas — a "infecção" sumiu
        $this->assertSame(0, Veiculo::where('placa', 'like', '%#%')->count());
        $this->assertSame(0, Veiculo::where('placa', 'like', '%-%')->count());

        $this->assertGreaterThan(0, Veiculo::count());
        $this->assertGreaterThan(0, Revisao::count());
    }
}
