<?php

namespace Tests\Feature;

use App\Models\Pessoa;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes do CRUD de pessoas pela API.
 * Rode com: php artisan test --filter=PessoaApiTest
 */
class PessoaApiTest extends TestCase
{
    use RefreshDatabase;

    /** Dados de uma pessoa válida, usados em vários testes */
    private function dadosValidos(): array
    {
        return [
            'nome' => 'Maria da Silva',
            'cpf' => '111.222.333-44',
            'sexo' => 'F',
            'data_nascimento' => '1990-05-10',
            'telefone' => '(11) 99999-1234',
            'email' => 'maria@exemplo.com',
        ];
    }

    public function test_cadastra_pessoa(): void
    {
        $resposta = $this->postJson('/api/pessoas', $this->dadosValidos());

        $resposta->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.nome', 'Maria da Silva');

        $this->assertDatabaseHas('pessoas', ['cpf' => '111.222.333-44']);
    }

    public function test_nao_cadastra_pessoa_com_campos_invalidos(): void
    {
        $resposta = $this->postJson('/api/pessoas', [
            'nome' => '',
            'cpf' => '111.222.333-44',
            'sexo' => 'X', // sexo fora do padrão M/F
            'data_nascimento' => '2090-01-01', // data futura
            'telefone' => '(11) 99999-1234',
            'email' => 'email-invalido',
        ]);

        $resposta->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_nao_exclui_pessoa_com_veiculos(): void
    {
        $pessoa = Pessoa::create($this->dadosValidos());

        Veiculo::create([
            'pessoa_id' => $pessoa->id,
            'marca' => 'Fiat',
            'modelo' => 'Uno',
            'ano' => 2015,
            'placa' => 'ABC-1234',
        ]);

        $resposta = $this->deleteJson('/api/pessoas/' . $pessoa->id);

        $resposta->assertStatus(409)
            ->assertJson(['success' => false]);

        // A pessoa continua no banco
        $this->assertDatabaseHas('pessoas', ['id' => $pessoa->id]);
    }
}
