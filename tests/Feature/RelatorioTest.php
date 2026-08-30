<?php

namespace Tests\Feature;

use App\Models\Pessoa;
use App\Models\Revisao;
use App\Models\Veiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes das consultas de relatórios.
 * Rode com: php artisan test --filter=RelatorioTest
 */
class RelatorioTest extends TestCase
{
    use RefreshDatabase;

    public function test_relatorios_respondem_com_sucesso(): void
    {
        // Sem dados no banco, o esperado é 200 com lista vazia
        foreach (['veiculos', 'veiculos-por-pessoa', 'pessoas', 'marcas-com-mais-revisoes', 'proximas-revisoes'] as $rota) {
            $this->getJson('/api/relatorios/' . $rota)
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        }
    }

    public function test_relatorio_proximas_revisoes_estima_a_data(): void
    {
        $pessoa = Pessoa::create([
            'nome' => 'Carlos Pereira',
            'cpf' => '555.444.333-22',
            'sexo' => 'M',
            'data_nascimento' => '1978-11-02',
            'telefone' => '(41) 96666-5555',
            'email' => 'carlos@exemplo.com',
        ]);

        $veiculo = Veiculo::create([
            'pessoa_id' => $pessoa->id,
            'marca' => 'Volkswagen',
            'modelo' => 'Gol',
            'ano' => 2019,
            'placa' => 'GOL-1234',
        ]);

        // Duas revisões com 90 dias de diferença
        Revisao::create([
            'veiculo_id' => $veiculo->id,
            'data_revisao' => '2026-01-01',
            'quilometragem' => 30000,
            'descricao' => 'Revisão 1',
            'valor' => 200.00,
        ]);
        Revisao::create([
            'veiculo_id' => $veiculo->id,
            'data_revisao' => '2026-04-01',
            'quilometragem' => 35000,
            'descricao' => 'Revisão 2',
            'valor' => 250.00,
        ]);

        // Próxima = última (01/04/2026) + média (90 dias) = 30/06/2026
        $this->getJson('/api/relatorios/proximas-revisoes')
            ->assertStatus(200)
            ->assertJsonPath('data.linhas.0.proxima_revisao', '30/06/2026');
    }
}
