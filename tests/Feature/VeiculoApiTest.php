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
            'cpf' => '39053344705', // CPF com dígitos verificadores válidos
            'sexo' => 'M',
            'data_nascimento' => '1985-03-20',
            'telefone' => '21988887777',
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
            'placa' => 'XYZ9999',
        ]);

        $resposta->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.placa', 'XYZ9999');

        $this->assertDatabaseHas('veiculos', ['placa' => 'XYZ9999']);
    }

    public function test_normaliza_placa_para_maiuscula_sem_hiphen(): void
    {
        $pessoa = $this->criarPessoa();

        $resposta = $this->postJson('/api/veiculos', [
            'pessoa_id' => $pessoa->id,
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'ano' => 2020,
            'placa' => 'xyz-9999', // minúscula e com hífen
        ]);

        $resposta->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.placa', 'XYZ9999');

        $this->assertDatabaseHas('veiculos', ['placa' => 'XYZ9999']);
    }

    public function test_aceita_placa_no_formato_mercosul(): void
    {
        $pessoa = $this->criarPessoa();

        $resposta = $this->postJson('/api/veiculos', [
            'pessoa_id' => $pessoa->id,
            'marca' => 'Fiat',
            'modelo' => 'Argo',
            'ano' => 2022,
            'placa' => 'abc1d23', // Mercosul, minúscula
        ]);

        $resposta->assertStatus(201)
            ->assertJsonPath('data.placa', 'ABC1D23');
    }

    public function test_nao_cadastra_veiculo_com_placa_invalida(): void
    {
        $pessoa = $this->criarPessoa();

        foreach (['ABC12', 'AB12345', 'ABC1D234', 'ABC-12X4'] as $placa) {
            $resposta = $this->postJson('/api/veiculos', [
                'pessoa_id' => $pessoa->id,
                'marca' => 'Fiat',
                'modelo' => 'Uno',
                'ano' => 2015,
                'placa' => $placa,
            ]);

            $resposta->assertStatus(422)
                ->assertJsonStructure(['errors' => ['placa']]);
        }
    }

    public function test_lista_veiculos_paginada_e_filtrada_por_pessoa(): void
    {
        $pessoa = $this->criarPessoa();

        $outraPessoa = Pessoa::create([
            'nome' => 'Outra Pessoa',
            'cpf' => '93541134780', // CPF com dígitos verificadores válidos
            'sexo' => 'F',
            'data_nascimento' => '1992-01-25',
            'telefone' => '11988887777',
            'email' => 'outra@exemplo.com',
        ]);

        // Dois veículos para a primeira pessoa e um para a segunda
        foreach (['XYZ9999', 'ABC1234'] as $placa) {
            $this->postJson('/api/veiculos', [
                'pessoa_id' => $pessoa->id,
                'marca' => 'Toyota',
                'modelo' => 'Corolla',
                'ano' => 2020,
                'placa' => $placa,
            ])->assertStatus(201);
        }

        $this->postJson('/api/veiculos', [
            'pessoa_id' => $outraPessoa->id,
            'marca' => 'Fiat',
            'modelo' => 'Uno',
            'ano' => 2015,
            'placa' => 'MNO5678',
        ])->assertStatus(201);

        // Sem filtro: 3 veículos no total, com a meta de paginação
        $this->getJson('/api/veiculos')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
                'meta' => ['current_page', 'total', 'per_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 3);

        // Filtro por pessoa: só os 2 dela
        $this->getJson('/api/veiculos?pessoa_id=' . $pessoa->id)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        // Filtro com página pequena o suficiente para caber tudo
        $this->getJson('/api/veiculos?per_page=2&pessoa_id=' . $pessoa->id)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 1);
    }

    public function test_lista_veiculos_filtra_por_busca(): void
    {
        $pessoa = $this->criarPessoa();

        $outraPessoa = Pessoa::create([
            'nome' => 'Maria Souza',
            'cpf' => '93541134780', // CPF com dígitos verificadores válidos
            'sexo' => 'F',
            'data_nascimento' => '1992-01-25',
            'telefone' => '11988887777',
            'email' => 'maria@exemplo.com',
        ]);

        // Dois Toyotas (um de cada pessoa) e um Honda, para a busca
        // por marca pegar só os correspondentes
        $veiculos = [
            ['pessoa_id' => $pessoa->id, 'marca' => 'Toyota', 'modelo' => 'Corolla', 'ano' => 2020, 'placa' => 'XYZ9999'],
            ['pessoa_id' => $pessoa->id, 'marca' => 'Honda', 'modelo' => 'Civic', 'ano' => 2019, 'placa' => 'ABC1234'],
            ['pessoa_id' => $outraPessoa->id, 'marca' => 'Toyota', 'modelo' => 'Hilux', 'ano' => 2022, 'placa' => 'MNO5678'],
        ];

        foreach ($veiculos as $veiculo) {
            $this->postJson('/api/veiculos', $veiculo)->assertStatus(201);
        }

        // Por marca
        $this->getJson('/api/veiculos?busca=toyota')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        // Por placa, aceitando hífen e trecho parcial ("xyz-99" acha XYZ9999)
        $this->getJson('/api/veiculos?busca=xyz-99')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.placa', 'XYZ9999');

        // Por nome do proprietário
        $this->getJson('/api/veiculos?busca=souza')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.placa', 'MNO5678');

        // Por modelo
        $this->getJson('/api/veiculos?busca=hilux')
            ->assertJsonCount(1, 'data');

        // Busca sem resultado: página vazia, mas com a meta
        $this->getJson('/api/veiculos?busca=zzzzzz')
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_nao_cadastra_veiculo_sem_proprietario(): void
    {
        $resposta = $this->postJson('/api/veiculos', [
            'marca' => 'Fiat',
            'modelo' => 'Uno',
            'ano' => 2015,
            'placa' => 'ABC1234',
        ]);

        $resposta->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
