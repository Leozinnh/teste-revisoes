<template>
    <div>

        <!-- Seleção do relatório -->
        <div class="mb-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <label class="mb-1 block text-sm font-medium text-slate-600">Relatório</label>
            <select
                v-model="relatorioSelecionado"
                @change="carregarRelatorio()"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200 md:max-w-md"
            >
                <optgroup v-for="grupo in gruposRelatorios" :key="grupo.titulo" :label="grupo.titulo">
                    <option v-for="relatorio in grupo.relatorios" :key="relatorio.rota" :value="relatorio.rota">
                        {{ relatorio.titulo }}
                    </option>
                </optgroup>
            </select>

            <!-- Filtros de período (relatório de revisões) -->
            <div v-if="relatorioAtual && relatorioAtual.filtros" class="mt-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Data inicial</label>
                    <input v-model="filtros.data_inicio" type="date"
                           class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Data final</label>
                    <input v-model="filtros.data_fim" type="date"
                           class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200">
                </div>
                <button
                    @click="carregarRelatorio()"
                    class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700"
                >
                    Filtrar
                </button>
            </div>
        </div>

        <!-- Resultado -->
        <div v-if="dados" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-800">{{ dados.titulo }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ descricaoAtual }}</p>

            <!-- Gráfico -->
            <div v-if="dados.grafico" class="mt-6 h-72">
                <canvas ref="grafico"></canvas>
            </div>

            <!-- Tabela (as colunas vêm da resposta da API) -->
            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th v-for="coluna in dados.colunas" :key="coluna.chave" class="px-4 py-3">
                                {{ coluna.rotulo }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(linha, indice) in dados.linhas" :key="indice" class="hover:bg-slate-50">
                            <td v-for="coluna in dados.colunas" :key="coluna.chave" class="px-4 py-3 text-slate-600">
                                {{ formatarCelula(linha[coluna.chave]) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p v-if="dados.linhas.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">
                    Nenhum registro encontrado para este relatório.
                </p>
            </div>
        </div>

        <p v-if="erro" class="mt-8 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ erro }}
        </p>

    </div>
</template>

<script>
import axios from '../bootstrap';
import criarGrafico from '../chart';

export default {
    name: 'Relatorios',

    data() {
        return {
            // Os 12 relatórios; cada um aponta para um endpoint da API
            gruposRelatorios: [
                {
                    titulo: 'Veículos',
                    relatorios: [
                        { rota: 'veiculos', titulo: 'Todos os veículos', descricao: 'Listagem completa, com o proprietário de cada veículo.' },
                        { rota: 'veiculos-por-pessoa', titulo: 'Veículos por pessoa', descricao: 'Veículos agrupados pelo proprietário, em ordem alfabética.' },
                        { rota: 'sexo-com-mais-veiculos', titulo: 'Quem possui mais veículos: homens ou mulheres', descricao: 'Total de veículos por sexo do proprietário.' },
                        { rota: 'marcas-por-quantidade', titulo: 'Marcas pela quantidade de veículos', descricao: 'Marcas ordenadas pela quantidade de veículos cadastrados.' },
                        { rota: 'marcas-por-sexo', titulo: 'Marcas distintas por sexo', descricao: 'Total de marcas diferentes possuídas por homens e por mulheres.' },
                    ],
                },
                {
                    titulo: 'Pessoas',
                    relatorios: [
                        { rota: 'pessoas', titulo: 'Todas as pessoas', descricao: 'Listagem completa das pessoas cadastradas.' },
                        { rota: 'pessoas-por-sexo', titulo: 'Pessoas por sexo com idade média', descricao: 'Quantidade de homens e mulheres e a idade média de cada grupo.' },
                    ],
                },
                {
                    titulo: 'Revisões',
                    relatorios: [
                        { rota: 'revisoes-por-periodo', titulo: 'Revisões por período', descricao: 'Revisões dentro de um intervalo de datas.', filtros: true },
                        { rota: 'marcas-com-mais-revisoes', titulo: 'Marcas com maior número de revisões', descricao: 'Quais marcas mais passam por revisões.' },
                        { rota: 'pessoas-com-mais-revisoes', titulo: 'Pessoas com maior número de revisões', descricao: 'Quais proprietários mais revisam seus veículos.' },
                        { rota: 'media-tempo-entre-revisoes', titulo: 'Média de tempo entre revisões (por pessoa)', descricao: 'Intervalo médio, em dias, entre uma revisão e outra de cada pessoa.' },
                        { rota: 'proximas-revisoes', titulo: 'Próximas revisões estimadas', descricao: 'Estimativa da próxima revisão, com base no histórico de cada veículo.' },
                    ],
                },
            ],
            relatorioSelecionado: 'veiculos',
            filtros: {
                data_inicio: '',
                data_fim: '',
            },
            dados: null,
            erro: null,
            grafico: null,
        };
    },

    computed: {
        relatorioAtual() {
            const grupos = this.gruposRelatorios;
            for (let i = 0; i < grupos.length; i++) {
                const relatorio = grupos[i].relatorios.find((item) => item.rota === this.relatorioSelecionado);
                if (relatorio) {
                    return relatorio;
                }
            }
            return null;
        },

        descricaoAtual() {
            return this.relatorioAtual ? this.relatorioAtual.descricao : '';
        },
    },

    mounted() {
        this.carregarRelatorio();
    },

    beforeDestroy() {
        if (this.grafico) {
            this.grafico.destroy();
        }
    },

    methods: {
        async carregarRelatorio() {
            this.erro = null;

            try {
                const resposta = await axios.get('/relatorios/' + this.relatorioSelecionado, {
                    params: this.filtros,
                });
                this.dados = resposta.data.data;
                this.$nextTick(() => {
                    this.renderizarGrafico();
                });
            } catch (erro) {
                this.erro = 'Não foi possível carregar o relatório.';
            }
        },

        renderizarGrafico() {
            if (!this.dados.grafico || !this.$refs.grafico) {
                return;
            }

            if (this.grafico) {
                this.grafico.destroy();
            }

            this.grafico = criarGrafico(
                this.$refs.grafico,
                this.dados.grafico.tipo,
                this.dados.grafico.rotulos,
                this.dados.grafico.valores,
                this.dados.grafico.titulo
            );
        },

        formatarCelula(valor) {
            if (valor === null || valor === undefined) {
                return '—';
            }
            return String(valor);
        },
    },
};
</script>
