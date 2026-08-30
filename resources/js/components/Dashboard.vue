<template>
    <div>

        <!-- Indicadores -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Pessoas</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ resumo.pessoas }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Veículos</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ resumo.veiculos }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Revisões</p>
                <p class="mt-2 text-3xl font-bold text-slate-800">{{ resumo.revisoes }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total gasto em revisões</p>
                <p class="mt-2 text-3xl font-bold text-emerald-700">{{ formatarMoeda(resumo.total_gasto) }}</p>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Veículos por marca</h3>
                <div class="h-64">
                    <canvas ref="graficoMarcas"></canvas>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Revisões por mês</h3>
                <div class="h-64">
                    <canvas ref="graficoMeses"></canvas>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h3 class="mb-4 text-sm font-semibold text-slate-700">Pessoas por sexo</h3>
                <div class="h-64">
                    <canvas ref="graficoSexo"></canvas>
                </div>
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
    name: 'Dashboard',

    data() {
        return {
            resumo: {
                pessoas: 0,
                veiculos: 0,
                revisoes: 0,
                total_gasto: 0,
            },
            erro: null,
            graficos: [],
        };
    },

    mounted() {
        this.carregarResumo();
    },

    // Destrói os gráficos ao sair da página (evita vazamento de memória)
    beforeDestroy() {
        this.graficos.forEach((grafico) => grafico.destroy());
    },

    methods: {
        async carregarResumo() {
            try {
                const resposta = await axios.get('/dashboard');
                this.resumo = resposta.data.data;
                this.renderizarGraficos();
            } catch (erro) {
                this.erro = 'Não foi possível carregar os indicadores.';
            }
        },

        renderizarGraficos() {
            this.graficos.push(
                criarGrafico(this.$refs.graficoMarcas, 'bar', this.resumo.grafico_marcas.rotulos, this.resumo.grafico_marcas.valores, 'Veículos'),
                criarGrafico(this.$refs.graficoMeses, 'line', this.resumo.grafico_meses.rotulos, this.resumo.grafico_meses.valores, 'Revisões'),
                criarGrafico(this.$refs.graficoSexo, 'pie', this.resumo.grafico_sexo.rotulos, this.resumo.grafico_sexo.valores, 'Pessoas')
            );
        },

        formatarMoeda(valor) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(valor));
        },
    },
};
</script>
