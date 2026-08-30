<?php

namespace Tests\Feature;

use App\Models\Pessoa;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes do CRUD de revisões pela API.
 * Rode com: php artisan test --filter=RevisaoApiTest
 */
class RevisaoApiTest extends TestCase
{
    use RefreshDatabase;

    private function criarVeiculo(): Veiculo
    {
        $pessoa = Pessoa::create([
            'nome' => 'Ana Oliveira',
            'cpf' => '123.456.789-00',
            'sexo' => 'F',
            'data_nascimento' => '1995-07-15',
            'telefone' => '(31) 97777-6666',
            'email' => 'ana@exemplo.com',
        ]);

        return Veiculo::create([
            'pessoa_id' => $pessoa->id,
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'ano' => 2018,
            'placa' => 'HON-2020',
        ]);
    }

    public function test_cadastra_revisao(): void
    {
        $veiculo = $this->criarVeiculo();

        $resposta = $this->postJson('/api/revisoes', [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => '2026-08-15',
            'quilometragem' => 45000,
            'descricao' => 'Troca de óleo e filtros',
            'valor' => 350.00,
            'observacoes' => null,
        ]);

        $resposta->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('revisoes', ['veiculo_id' => $veiculo->id, 'valor' => 350.00]);
    }

    public function test_nao_cadastra_revisao_sem_campos_obrigatorios(): void
    {
        $resposta = $this->postJson('/api/revisoes', [
            'veiculo_id' => null,
            'data_revisao' => '',
            'quilometragem' => -5, // km negativa
            'descricao' => '',
            'valor' => -10,
        ]);

        $resposta->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
