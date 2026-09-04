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
            'cpf' => '52998224725', // CPF com dígitos verificadores válidos
            'sexo' => 'F',
            'data_nascimento' => '1990-05-10',
            'telefone' => '11999991234',
            'email' => 'maria@exemplo.com',
        ];
    }

    public function test_cadastra_pessoa(): void
    {
        $resposta = $this->postJson('/api/pessoas', $this->dadosValidos());

        $resposta->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.nome', 'Maria da Silva');

        $this->assertDatabaseHas('pessoas', ['cpf' => '52998224725']);
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

    public function test_normaliza_cpf_telefone_email_e_nome_antes_de_salvar(): void
    {
        $resposta = $this->postJson('/api/pessoas', [
            'nome' => '  Ana Souza  ',
            'cpf' => '529.982.247-25', // com máscara
            'sexo' => 'F',
            'data_nascimento' => '1992-03-11',
            'telefone' => '(11) 98888-7777', // com máscara
            'email' => '  Ana@Exemplo.COM  ', // caixa alta e espaços
        ]);

        $resposta->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pessoas', [
            'nome' => 'Ana Souza',
            'cpf' => '52998224725',
            'telefone' => '11988887777',
            'email' => 'ana@exemplo.com',
        ]);
    }

    public function test_nao_cadastra_pessoa_com_cpf_de_digito_verificador_invalido(): void
    {
        $dados = $this->dadosValidos();
        $dados['cpf'] = '52998224726'; // troca o último dígito do CPF válido

        $this->postJson('/api/pessoas', $dados)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cpf']]);
    }

    public function test_nao_cadastra_pessoa_com_cpf_de_digitos_repetidos(): void
    {
        $dados = $this->dadosValidos();
        $dados['cpf'] = '11111111111'; // passa no módulo 11, mas é inválido

        $this->postJson('/api/pessoas', $dados)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cpf']]);
    }

    public function test_nao_cadastra_pessoa_com_telefone_invalido(): void
    {
        $dados = $this->dadosValidos();
        $dados['telefone'] = '119999999'; // 9 dígitos, sem DDD completo

        $this->postJson('/api/pessoas', $dados)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['telefone']]);
    }

    public function test_nao_cadastra_pessoa_com_nascimento_anterior_a_1900(): void
    {
        $dados = $this->dadosValidos();
        $dados['data_nascimento'] = '1899-12-31';

        $this->postJson('/api/pessoas', $dados)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['data_nascimento']]);
    }

    public function test_nao_duplica_pessoa_com_cpf_de_grafia_diferente(): void
    {
        Pessoa::create($this->dadosValidos());

        $dados = $this->dadosValidos();
        $dados['cpf'] = '529.982.247-25'; // mesmo CPF, com máscara
        $dados['email'] = 'outra@exemplo.com';

        $this->postJson('/api/pessoas', $dados)
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['cpf']]);
    }

    public function test_lista_pessoas_paginada_com_meta(): void
    {
        Pessoa::create($this->dadosValidos());

        $segunda = $this->dadosValidos();
        $segunda['cpf'] = '93541134780'; // outro CPF com DV válido
        $segunda['email'] = 'segunda@exemplo.com';
        Pessoa::create($segunda);

        $this->getJson('/api/pessoas')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
                'meta' => ['current_page', 'total', 'per_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 25)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 1);

        // Uma por página: vira 2 páginas
        $this->getJson('/api/pessoas?per_page=1')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_nao_exclui_pessoa_com_veiculos(): void
    {
        $pessoa = Pessoa::create($this->dadosValidos());

        Veiculo::create([
            'pessoa_id' => $pessoa->id,
            'marca' => 'Fiat',
            'modelo' => 'Uno',
            'ano' => 2015,
            'placa' => 'ABC1234',
        ]);

        $resposta = $this->deleteJson('/api/pessoas/' . $pessoa->id);

        $resposta->assertStatus(409)
            ->assertJson(['success' => false]);

        // A pessoa continua no banco
        $this->assertDatabaseHas('pessoas', ['id' => $pessoa->id]);
    }
}
