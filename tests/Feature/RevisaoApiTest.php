<?php

namespace Tests\Feature;

use App\Models\Pessoa;
use App\Models\Revisao;
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

    private function criarVeiculo(?Pessoa $pessoa = null, string $placa = 'HON2020'): Veiculo
    {
        // Aceita uma pessoa já criada: o e-mail/CPF dela são únicos no banco,
        // então cada teste não pode criar a "Ana Oliveira" mais de uma vez
        $pessoa ??= Pessoa::create([
            'nome' => 'Ana Oliveira',
            'cpf' => '11144477735', // CPF com dígitos verificadores válidos
            'sexo' => 'F',
            'data_nascimento' => '1995-07-15',
            'telefone' => '31977776666',
            'email' => 'ana@exemplo.com',
        ]);

        return Veiculo::create([
            'pessoa_id' => $pessoa->id,
            'marca' => 'Honda',
            'modelo' => 'Civic',
            'ano' => 2018,
            'placa' => $placa,
        ]);
    }

    public function test_cadastra_revisao(): void
    {
        $veiculo = $this->criarVeiculo();

        $resposta = $this->postJson('/api/revisoes', [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(10)->format('Y-m-d'),
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

    public function test_nao_cadastra_revisao_com_quilometragem_acima_do_teto(): void
    {
        $veiculo = $this->criarVeiculo();

        $this->postJson('/api/revisoes', [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(10)->format('Y-m-d'),
            'quilometragem' => 2500000, // acima de 2.000.000
            'descricao' => 'Revisão',
            'valor' => 100.00,
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quilometragem']]);
    }

    public function test_nao_cadastra_revisao_com_valor_acima_do_teto(): void
    {
        $veiculo = $this->criarVeiculo();

        $this->postJson('/api/revisoes', [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(10)->format('Y-m-d'),
            'quilometragem' => 10000,
            'descricao' => 'Revisão',
            'valor' => 100000000, // acima do decimal(10,2) = 99.999.999,99
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['valor']]);
    }

    public function test_nao_cadastra_revisao_com_valor_de_mais_de_duas_casas(): void
    {
        $veiculo = $this->criarVeiculo();

        $this->postJson('/api/revisoes', [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(10)->format('Y-m-d'),
            'quilometragem' => 10000,
            'descricao' => 'Revisão',
            'valor' => 1.999, // 3 casas decimais
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['valor']]);
    }

    public function test_nao_cadastra_revisao_com_data_fora_dos_limites(): void
    {
        $veiculo = $this->criarVeiculo();
        $base = [
            'veiculo_id' => $veiculo->id,
            'quilometragem' => 10000,
            'descricao' => 'Revisão',
            'valor' => 100.00,
        ];

        // Antes de 1900
        $this->postJson('/api/revisoes', $base + ['data_revisao' => '1899-12-31'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['data_revisao']]);

        // No futuro
        $this->postJson('/api/revisoes', $base + ['data_revisao' => now()->addDay()->format('Y-m-d')])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['data_revisao']]);
    }

    public function test_nao_cadastra_revisao_com_km_regredindo(): void
    {
        $veiculo = $this->criarVeiculo();

        // Primeira revisão com 50.000 km (aceita: ainda não há histórico)
        $this->postJson('/api/revisoes', [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(10)->format('Y-m-d'),
            'quilometragem' => 50000,
            'descricao' => 'Revisão de 50 mil',
            'valor' => 300.00,
        ])->assertStatus(201);

        // Nova revisão com km menor → bloqueada
        $this->postJson('/api/revisoes', [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->format('Y-m-d'),
            'quilometragem' => 30000,
            'descricao' => 'Km menor',
            'valor' => 100.00,
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quilometragem']]);

        // Km igual à última registrada → aceita
        $this->postJson('/api/revisoes', [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->format('Y-m-d'),
            'quilometragem' => 50000,
            'descricao' => 'Km igual',
            'valor' => 100.00,
        ])->assertStatus(201);
    }

    public function test_lista_revisoes_paginada_e_filtrada_por_veiculo(): void
    {
        $veiculoA = $this->criarVeiculo();
        $veiculoB = $this->criarVeiculo($veiculoA->pessoa, 'HON3030'); // mesma pessoa, outro veículo

        // Duas revisões no veículo A e uma no B
        $revisoes = [
            ['veiculo' => $veiculoA, 'dias' => 3],
            ['veiculo' => $veiculoA, 'dias' => 2],
            ['veiculo' => $veiculoB, 'dias' => 1],
        ];

        foreach ($revisoes as $indice => $item) {
            $this->postJson('/api/revisoes', [
                'veiculo_id' => $item['veiculo']->id,
                'data_revisao' => now()->subDays($item['dias'])->format('Y-m-d'),
                'quilometragem' => 40000 + $indice,
                'descricao' => 'Revisão ' . ($indice + 1),
                'valor' => 150.00,
            ])->assertStatus(201);
        }

        // Sem filtro: as 3 revisões, com a meta de paginação
        $this->getJson('/api/revisoes')
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [],
                'meta' => ['current_page', 'total', 'per_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 25);

        // Filtro por veículo: só as 2 do veículo A
        $this->getJson('/api/revisoes?veiculo_id=' . $veiculoA->id)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        // Página pequena: uma revisão por página, em 3 páginas
        $this->getJson('/api/revisoes?per_page=1')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_edicao_de_km_ignora_a_propria_revisao(): void
    {
        $veiculo = $this->criarVeiculo();

        // Revisão mais antiga, com km menor
        Revisao::create([
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(20)->format('Y-m-d'),
            'quilometragem' => 20000,
            'descricao' => 'Revisão antiga',
            'valor' => 100.00,
        ]);

        $recente = Revisao::create([
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(5)->format('Y-m-d'),
            'quilometragem' => 50000,
            'descricao' => 'Revisão recente',
            'valor' => 300.00,
        ]);

        // Corrige a revisão mais recente para 30.000 (acima dos 20.000 da
        // anterior, mas abaixo do valor digitado por engano) → aceita
        $this->putJson('/api/revisoes/' . $recente->id, [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(5)->format('Y-m-d'),
            'quilometragem' => 30000,
            'descricao' => 'Revisão recente',
            'valor' => 300.00,
        ])->assertStatus(200);

        // Abaixo da km da outra revisão (20.000) → bloqueada
        $this->putJson('/api/revisoes/' . $recente->id, [
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subDays(5)->format('Y-m-d'),
            'quilometragem' => 15000,
            'descricao' => 'Revisão recente',
            'valor' => 300.00,
        ])->assertStatus(422)
            ->assertJsonStructure(['errors' => ['quilometragem']]);
    }
}
