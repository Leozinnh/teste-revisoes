<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\Revisao;
use App\Models\Veiculo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Relatórios do sistema.
 *
 * Resposta padrão: data: { titulo, colunas, linhas, grafico }.
 * O frontend monta a tabela e o gráfico a partir desses campos.
 */
class RelatorioController extends Controller
{
    // Prazo padrão entre revisões, usado para veículos com menos de
    // 2 revisões (sem histórico para calcular a média)
    private const DIAS_PADRAO_ENTRE_REVISOES = 180;

    // -------- Veículos --------

    // 1. Todos os veículos, com o proprietário
    public function veiculos(): JsonResponse
    {
        $veiculos = Veiculo::with('pessoa:id,nome')->orderBy('marca')->orderBy('modelo')->get();

        $linhas = $veiculos->map(fn ($v) => [
            'placa' => $v->placa,
            'marca' => $v->marca,
            'modelo' => $v->modelo,
            'ano' => $v->ano,
            'proprietario' => $v->pessoa->nome,
        ]);

        $porMarca = $veiculos->groupBy('marca')->map->count()->sortDesc();

        return $this->responder(
            'Todos os veículos',
            [['chave' => 'placa', 'rotulo' => 'Placa'], ['chave' => 'marca', 'rotulo' => 'Marca'], ['chave' => 'modelo', 'rotulo' => 'Modelo'], ['chave' => 'ano', 'rotulo' => 'Ano'], ['chave' => 'proprietario', 'rotulo' => 'Proprietário']],
            $linhas,
            $this->grafico('bar', $porMarca->keys(), $porMarca->values(), 'Veículos por marca')
        );
    }

    // 2. Veículos por pessoa, ordenados pelo nome do proprietário
    public function veiculosPorPessoa(): JsonResponse
    {
        $veiculos = Veiculo::with('pessoa:id,nome')
            ->get()
            ->sortBy('pessoa.nome')
            ->values();

        $linhas = $veiculos->map(fn ($v) => [
            'proprietario' => $v->pessoa->nome,
            'placa' => $v->placa,
            'marca' => $v->marca,
            'modelo' => $v->modelo,
            'ano' => $v->ano,
        ]);

        $porPessoa = $veiculos->groupBy('pessoa.nome')->map->count()->sortDesc();

        return $this->responder(
            'Veículos por pessoa',
            [['chave' => 'proprietario', 'rotulo' => 'Proprietário'], ['chave' => 'placa', 'rotulo' => 'Placa'], ['chave' => 'marca', 'rotulo' => 'Marca'], ['chave' => 'modelo', 'rotulo' => 'Modelo'], ['chave' => 'ano', 'rotulo' => 'Ano']],
            $linhas,
            $this->grafico('bar', $porPessoa->keys()->take(10), $porPessoa->values()->take(10), 'Veículos por pessoa (top 10)')
        );
    }

    // 3. Quem possui mais veículos: homens ou mulheres
    public function sexoComMaisVeiculos(): JsonResponse
    {
        $porSexo = Pessoa::withCount('veiculos')
            ->get()
            ->groupBy('sexo')
            ->map(fn ($grupo) => [
                'sexo' => $grupo->first()->sexo === 'M' ? 'Masculino' : 'Feminino',
                'total_veiculos' => $grupo->sum('veiculos_count'),
            ])
            ->sortByDesc('total_veiculos')
            ->values();

        return $this->responder(
            'Veículos por sexo do proprietário',
            [['chave' => 'sexo', 'rotulo' => 'Sexo'], ['chave' => 'total_veiculos', 'rotulo' => 'Total de veículos']],
            $porSexo,
            $this->grafico('pie', $porSexo->pluck('sexo'), $porSexo->pluck('total_veiculos'), 'Veículos por sexo')
        );
    }

    // 4. Marcas ordenadas pela quantidade de veículos
    public function marcasPorQuantidade(): JsonResponse
    {
        $marcas = Veiculo::selectRaw('marca, count(*) as total')
            ->groupBy('marca')
            ->orderByDesc('total')
            ->get();

        $linhas = $marcas->map(fn ($m) => ['marca' => $m->marca, 'quantidade' => $m->total]);

        return $this->responder(
            'Marcas pela quantidade de veículos',
            [['chave' => 'marca', 'rotulo' => 'Marca'], ['chave' => 'quantidade', 'rotulo' => 'Quantidade']],
            $linhas,
            $this->grafico('bar', $marcas->pluck('marca'), $marcas->pluck('total'), 'Veículos por marca')
        );
    }

    // 5. Total de marcas distintas separado entre homens e mulheres
    public function marcasPorSexo(): JsonResponse
    {
        $porSexo = \Illuminate\Support\Facades\DB::table('veiculos')
            ->join('pessoas', 'pessoas.id', '=', 'veiculos.pessoa_id')
            ->selectRaw("pessoas.sexo, count(distinct veiculos.marca) as total_marcas")
            ->groupBy('pessoas.sexo')
            ->orderByDesc('total_marcas')
            ->get();

        $linhas = $porSexo->map(fn ($linha) => [
            'sexo' => $linha->sexo === 'M' ? 'Masculino' : 'Feminino',
            'total_marcas' => $linha->total_marcas,
        ]);

        return $this->responder(
            'Marcas distintas por sexo do proprietário',
            [['chave' => 'sexo', 'rotulo' => 'Sexo'], ['chave' => 'total_marcas', 'rotulo' => 'Total de marcas']],
            $linhas,
            $this->grafico('bar', $linhas->pluck('sexo'), $linhas->pluck('total_marcas'), 'Marcas por sexo')
        );
    }

    // -------- Pessoas --------

    // 6. Todas as pessoas, com a quantidade de veículos
    public function pessoas(): JsonResponse
    {
        $pessoas = Pessoa::withCount('veiculos')->orderBy('nome')->get();

        $linhas = $pessoas->map(fn ($p) => [
            'nome' => $p->nome,
            'cpf' => $p->cpf,
            'sexo' => $p->sexo === 'M' ? 'Masculino' : 'Feminino',
            'data_nascimento' => $p->data_nascimento->format('d/m/Y'),
            'email' => $p->email,
            'telefone' => $p->telefone,
            'veiculos' => $p->veiculos_count,
        ]);

        $porSexo = $pessoas->groupBy('sexo')->map->count();

        return $this->responder(
            'Todas as pessoas',
            [['chave' => 'nome', 'rotulo' => 'Nome'], ['chave' => 'cpf', 'rotulo' => 'CPF'], ['chave' => 'sexo', 'rotulo' => 'Sexo'], ['chave' => 'data_nascimento', 'rotulo' => 'Nascimento'], ['chave' => 'email', 'rotulo' => 'E-mail'], ['chave' => 'telefone', 'rotulo' => 'Telefone'], ['chave' => 'veiculos', 'rotulo' => 'Veículos']],
            $linhas,
            $this->grafico('pie', ['Masculino', 'Feminino'], [$porSexo->get('M', 0), $porSexo->get('F', 0)], 'Pessoas por sexo')
        );
    }

    // 7. Pessoas por sexo, com a idade média
    public function pessoasPorSexo(): JsonResponse
    {
        // age(data_nascimento) é um recurso do PostgreSQL que calcula
        // a idade; extraímos os anos e tiramos a média
        $porSexo = Pessoa::selectRaw("sexo, count(*) as total, round(avg(extract(year from age(data_nascimento)))) as idade_media")
            ->groupBy('sexo')
            ->get();

        $linhas = $porSexo->map(fn ($linha) => [
            'sexo' => $linha->sexo === 'M' ? 'Masculino' : 'Feminino',
            'quantidade' => $linha->total,
            'idade_media' => $linha->idade_media . ' anos',
        ]);

        return $this->responder(
            'Pessoas por sexo com idade média',
            [['chave' => 'sexo', 'rotulo' => 'Sexo'], ['chave' => 'quantidade', 'rotulo' => 'Quantidade'], ['chave' => 'idade_media', 'rotulo' => 'Idade média']],
            $linhas,
            $this->grafico('bar', $linhas->pluck('sexo'), $linhas->pluck('quantidade'), 'Pessoas por sexo')
        );
    }

    // -------- Revisões --------

    // 8. Revisões dentro de um período. Filtros opcionais na URL:
    //    ?data_inicio=AAAA-MM-DD&data_fim=AAAA-MM-DD
    public function revisoesPorPeriodo(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ]);

        $revisoes = Revisao::with('veiculo.pessoa:id,nome')
            ->when($dados['data_inicio'] ?? null, fn ($query) => $query->whereDate('data_revisao', '>=', $dados['data_inicio']))
            ->when($dados['data_fim'] ?? null, fn ($query) => $query->whereDate('data_revisao', '<=', $dados['data_fim']))
            ->orderBy('data_revisao')
            ->get();

        $linhas = $revisoes->map(fn ($r) => [
            'data' => $r->data_revisao->format('d/m/Y'),
            'veiculo' => $r->veiculo->marca . ' ' . $r->veiculo->modelo . ' (' . $r->veiculo->placa . ')',
            'proprietario' => $r->veiculo->pessoa->nome,
            'quilometragem' => $r->quilometragem,
            'descricao' => $r->descricao,
            'valor' => $r->valor,
        ]);

        // Gráfico de revisões por mês dentro do mesmo período
        $porMes = $revisoes->groupBy(fn ($r) => $r->data_revisao->format('Y-m'))->map->count();

        return $this->responder(
            'Revisões por período',
            [['chave' => 'data', 'rotulo' => 'Data'], ['chave' => 'veiculo', 'rotulo' => 'Veículo'], ['chave' => 'proprietario', 'rotulo' => 'Proprietário'], ['chave' => 'quilometragem', 'rotulo' => 'Km'], ['chave' => 'descricao', 'rotulo' => 'Descrição'], ['chave' => 'valor', 'rotulo' => 'Valor']],
            $linhas,
            $this->grafico('line', $porMes->keys(), $porMes->values(), 'Revisões por mês')
        );
    }

    // 9. Marcas com maior número de revisões
    public function marcasComMaisRevisoes(): JsonResponse
    {
        $marcas = \Illuminate\Support\Facades\DB::table('revisoes')
            ->join('veiculos', 'veiculos.id', '=', 'revisoes.veiculo_id')
            ->selectRaw('veiculos.marca, count(*) as total')
            ->groupBy('veiculos.marca')
            ->orderByDesc('total')
            ->get();

        $linhas = $marcas->map(fn ($m) => ['marca' => $m->marca, 'quantidade' => $m->total]);

        return $this->responder(
            'Marcas com maior número de revisões',
            [['chave' => 'marca', 'rotulo' => 'Marca'], ['chave' => 'quantidade', 'rotulo' => 'Quantidade']],
            $linhas,
            $this->grafico('bar', $marcas->pluck('marca'), $marcas->pluck('total'), 'Revisões por marca')
        );
    }

    // 10. Pessoas com maior número de revisões
    public function pessoasComMaisRevisoes(): JsonResponse
    {
        $pessoas = \Illuminate\Support\Facades\DB::table('revisoes')
            ->join('veiculos', 'veiculos.id', '=', 'revisoes.veiculo_id')
            ->join('pessoas', 'pessoas.id', '=', 'veiculos.pessoa_id')
            ->selectRaw('pessoas.nome, count(*) as total')
            ->groupBy('pessoas.id', 'pessoas.nome')
            ->orderByDesc('total')
            ->get();

        $linhas = $pessoas->map(fn ($p) => ['pessoa' => $p->nome, 'quantidade' => $p->total]);

        return $this->responder(
            'Pessoas com maior número de revisões',
            [['chave' => 'pessoa', 'rotulo' => 'Pessoa'], ['chave' => 'quantidade', 'rotulo' => 'Quantidade']],
            $linhas,
            $this->grafico('bar', $pessoas->pluck('nome'), $pessoas->pluck('total'), 'Revisões por pessoa')
        );
    }

    // 11. Média de dias entre revisões de cada pessoa: junta as datas
    // de todos os veículos, calcula a diferença entre cada revisão
    // consecutiva e tira a média.
    public function mediaTempoEntreRevisoes(): JsonResponse
    {
        $pessoas = Pessoa::with(['veiculos.revisoes' => fn ($query) => $query->orderBy('data_revisao')])->get();

        $linhas = [];
        foreach ($pessoas as $pessoa) {
            $datas = $pessoa->veiculos
                ->flatMap(fn ($veiculo) => $veiculo->revisoes->pluck('data_revisao'))
                ->sort()
                ->values();

            // Sem histórico suficiente não dá para calcular a média
            if ($datas->count() < 2) {
                continue;
            }

            $total = 0;
            foreach ($datas as $i => $data) {
                if ($i > 0) {
                    $total += $datas[$i - 1]->diffInDays($data);
                }
            }

            $linhas[] = [
                'pessoa' => $pessoa->nome,
                'media_dias' => round($total / ($datas->count() - 1)),
            ];
        }

        usort($linhas, fn ($a, $b) => $b['media_dias'] <=> $a['media_dias']);

        return $this->responder(
            'Média de tempo entre revisões (por pessoa)',
            [['chave' => 'pessoa', 'rotulo' => 'Pessoa'], ['chave' => 'media_dias', 'rotulo' => 'Média (dias)']],
            $linhas,
            $this->grafico('bar', collect($linhas)->pluck('pessoa'), collect($linhas)->pluck('media_dias'), 'Média de dias entre revisões')
        );
    }

    // 12. Próximas revisões estimadas: última revisão + média de dias
    // entre as revisões anteriores do veículo (180 dias quando não há
    // histórico suficiente para calcular a média).
    public function proximasRevisoes(): JsonResponse
    {
        $veiculos = Veiculo::with('pessoa:id,nome', 'revisoes')->orderBy('marca')->get();

        $linhas = [];
        foreach ($veiculos as $veiculo) {
            if ($veiculo->revisoes->isEmpty()) {
                continue;
            }

            $datas = $veiculo->revisoes->sortBy('data_revisao')->pluck('data_revisao')->values();

            $intervalos = [];
            foreach ($datas as $i => $data) {
                if ($i > 0) {
                    $intervalos[] = $datas[$i - 1]->diffInDays($data);
                }
            }

            $mediaDias = count($intervalos) > 0
                ? round(collect($intervalos)->avg())
                : self::DIAS_PADRAO_ENTRE_REVISOES;

            $proximaRevisao = $datas->last()->copy()->addDays($mediaDias);

            $linhas[] = [
                'veiculo' => $veiculo->marca . ' ' . $veiculo->modelo . ' (' . $veiculo->placa . ')',
                'proprietario' => $veiculo->pessoa->nome,
                'ultima_revisao' => $datas->last()->format('d/m/Y'),
                'intervalo_medio_dias' => $mediaDias,
                'proxima_revisao' => $proximaRevisao->format('d/m/Y'),
            ];
        }

        $porMarca = $veiculos->filter(fn ($v) => $v->revisoes->isNotEmpty())
            ->groupBy('marca')
            ->map->count()
            ->sortDesc();

        return $this->responder(
            'Próximas revisões estimadas',
            [['chave' => 'veiculo', 'rotulo' => 'Veículo'], ['chave' => 'proprietario', 'rotulo' => 'Proprietário'], ['chave' => 'ultima_revisao', 'rotulo' => 'Última revisão'], ['chave' => 'intervalo_medio_dias', 'rotulo' => 'Intervalo médio (dias)'], ['chave' => 'proxima_revisao', 'rotulo' => 'Próxima revisão']],
            $linhas,
            $this->grafico('bar', $porMarca->keys(), $porMarca->values(), 'Veículos com revisão por marca')
        );
    }

    private function responder(string $titulo, array $colunas, $linhas, ?array $grafico = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'titulo' => $titulo,
                'colunas' => $colunas,
                'linhas' => $linhas,
                'grafico' => $grafico,
            ],
        ]);
    }

    private function grafico(string $tipo, $rotulos, $valores, string $titulo): array
    {
        return [
            'tipo' => $tipo,
            'rotulos' => $rotulos,
            'valores' => $valores,
            'titulo' => $titulo,
        ];
    }
}
