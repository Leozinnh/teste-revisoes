<?php

namespace Tests\Feature;

use App\Models\Pessoa;
use App\Models\Revisao;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes do resumo do dashboard.
 * Rode com: php artisan test --filter=DashboardApiTest
 */
class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumo_traz_indicadores_e_graficos(): void
    {
        $pessoa = Pessoa::create([
            'nome' => 'Fernanda Lima',
            'cpf' => '93541134780', // CPF com dígitos verificadores válidos
            'sexo' => 'F',
            'data_nascimento' => '1988-09-12',
            'telefone' => '41988889999',
            'email' => 'fernanda@exemplo.com',
        ]);

        $veiculo = Veiculo::create([
            'pessoa_id' => $pessoa->id,
            'marca' => 'Chevrolet',
            'modelo' => 'Onix',
            'ano' => 2021,
            'placa' => 'QWE4321',
        ]);

        // Duas revisões no mês atual (uma hoje, uma no dia 1º) e uma dois meses atrás
        $mesAtual = now()->format('Y-m');
        $outroMes = now()->subMonths(2)->startOfMonth()->format('Y-m');

        Revisao::create([
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->format('Y-m-d'),
            'quilometragem' => 35000,
            'descricao' => 'Revisão de 35 mil',
            'valor' => 250.00,
        ]);
        Revisao::create([
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->startOfMonth()->format('Y-m-d'),
            'quilometragem' => 30000,
            'descricao' => 'Revisão de 30 mil',
            'valor' => 200.00,
        ]);
        Revisao::create([
            'veiculo_id' => $veiculo->id,
            'data_revisao' => now()->subMonths(2)->startOfMonth()->format('Y-m-d'),
            'quilometragem' => 25000,
            'descricao' => 'Revisão antiga',
            'valor' => 100.00,
        ]);

        $resposta = $this->getJson('/api/dashboard');

        $resposta->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.pessoas', 1)
            ->assertJsonPath('data.veiculos', 1)
            ->assertJsonPath('data.revisoes', 3);

        // Só a presença da chave importa (o tipo devolvido varia por banco)
        $this->assertArrayHasKey('total_gasto', $resposta->json('data'));

        // Meses ordenados cronologicamente, com o mês atual entre os rótulos
        $meses = $resposta->json('data.grafico_meses');
        $this->assertSame([$outroMes, $mesAtual], $meses['rotulos']);
        $this->assertSame([1, 2], array_values($meses['valores']));

        // Sexo traduzido em rótulo legível
        $this->assertContains('Feminino', $resposta->json('data.grafico_sexo.rotulos'));

        // Marcas presentes
        $this->assertContains('Chevrolet', $resposta->json('data.grafico_marcas.rotulos'));
    }
}
