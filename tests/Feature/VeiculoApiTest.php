<?php

namespace Tests\Feature;

use App\Models\Pessoa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes do CRUD de veículos pela API.
 * Rode com: php artisan test --filter=VeiculoApiTest
 */
class VeiculoApiTest extends TestCase
{
    use RefreshDatabase;

    private function criarPessoa(): Pessoa
    {
        return Pessoa::create([
            'nome' => 'João Santos',
            'cpf' => '999.888.777-66',
            'sexo' => 'M',
            'data_nascimento' => '1985-03-20',
            'telefone' => '(21) 98888-7777',
            'email' => 'joao@exemplo.com',
        ]);
    }

    public function test_cadastra_veiculo(): void
    {
        $pessoa = $this->criarPessoa();

        $resposta = $this->postJson('/api/veiculos', [
            'pessoa_id' => $pessoa->id,
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'ano' => 2020,
            'placa' => 'XYZ-9999',
        ]);

        $resposta->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.placa', 'XYZ-9999');

        $this->assertDatabaseHas('veiculos', ['placa' => 'XYZ-9999']);
    }

    public function test_nao_cadastra_veiculo_sem_proprietario(): void
    {
        $resposta = $this->postJson('/api/veiculos', [
            'marca' => 'Fiat',
            'modelo' => 'Uno',
            'ano' => 2015,
            'placa' => 'ABC-1234',
        ]);

        $resposta->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
