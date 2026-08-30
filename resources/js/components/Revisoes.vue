<template>
    <div>

        <!-- Mensagem de sucesso ou erro -->
        <div
            v-if="mensagem"
            class="mb-6 rounded-lg border px-4 py-3 text-sm"
            :class="mensagem.tipo === 'erro'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
        >
            {{ mensagem.texto }}
        </div>

        <!-- Aviso de filtro vindo da tela de veículos -->
        <div
            v-if="veiculoFiltro"
            class="mb-6 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700"
        >
            Mostrando revisões do veículo <strong>{{ veiculoFiltro }}</strong>.
            <router-link to="/revisoes" class="ml-2 font-medium underline">Ver todas</router-link>
        </div>

        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-700">
                {{ editandoId ? 'Editando revisão' : 'Lista de revisões' }}
            </h2>
            <button
                v-if="!mostrandoFormulario"
                @click="abrirFormulario()"
                class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700"
            >
                + Nova revisão
            </button>
        </div>

        <!-- Formulário (cadastro e edição) -->
        <div v-if="mostrandoFormulario" class="mb-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Veículo *</label>
                    <select
                        v-model="form.veiculo_id"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.veiculo_id ? 'border-red-400' : 'border-slate-300'"
                    >
                        <option :value="null" disabled>Selecione o veículo</option>
                        <option v-for="veiculo in veiculos" :key="veiculo.id" :value="veiculo.id">
                            {{ veiculo.marca }} {{ veiculo.modelo }} ({{ veiculo.placa }}) · {{ veiculo.pessoa.nome }}
                        </option>
                    </select>
                    <p v-if="erros.veiculo_id" class="mt-1 text-xs text-red-600">{{ erros.veiculo_id[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Data da revisão *</label>
                    <input
                        v-model="form.data_revisao"
                        type="date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Quilometragem *</label>
                    <input
                        v-model.number="form.quilometragem"
                        type="number"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Valor (R$) *</label>
                    <input
                        v-model="form.valor"
                        type="text"
                        inputmode="decimal"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Descrição *</label>
                    <input
                        v-model="form.descricao"
                        type="text"
                        placeholder="Ex.: troca de óleo e filtros"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-600">Observações</label>
                    <textarea
                        v-model="form.observacoes"
                        rows="3"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                    ></textarea>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button
                    @click="salvar()"
                    class="rounded-lg bg-sky-600 px-5 py-2 text-sm font-medium text-white hover:bg-sky-700"
                >
                    Salvar
                </button>
                <button
                    @click="cancelar()"
                    class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Cancelar
                </button>
            </div>
        </div>

        <!-- Tabela -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Data</th>
                        <th class="px-4 py-3">Veículo</th>
                        <th class="px-4 py-3">Proprietário</th>
                        <th class="px-4 py-3">Km</th>
                        <th class="px-4 py-3">Descrição</th>
                        <th class="px-4 py-3">Valor</th>
                        <th class="px-4 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="revisao in revisoes" :key="revisao.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-600">{{ formatarData(revisao.data_revisao) }}</td>
                        <td class="px-4 py-3 font-medium text-slate-800">
                            {{ revisao.veiculo.marca }} {{ revisao.veiculo.modelo }}
                            <span class="text-slate-400">({{ revisao.veiculo.placa }})</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ revisao.veiculo.pessoa.nome }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ formatarNumero(revisao.quilometragem) }} km</td>
                        <td class="px-4 py-3 text-slate-600">{{ revisao.descricao }}</td>
                        <td class="px-4 py-3 font-medium text-emerald-700">{{ formatarMoeda(revisao.valor) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <button @click="editar(revisao)" class="mr-3 text-sky-600 hover:underline">Editar</button>
                            <button @click="excluir(revisao)" class="text-red-600 hover:underline">Excluir</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p v-if="carregando" class="px-4 py-6 text-center text-sm text-slate-500">Carregando...</p>
            <p v-else-if="revisoes.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">
                Nenhuma revisão cadastrada ainda.
            </p>
        </div>

    </div>
</template>

<script>
import axios from '../bootstrap';

export default {
    name: 'Revisoes',

    data() {
        return {
            revisoes: [],
            veiculos: [],
            // veículo filtrado pela URL (?veiculo_id=), vindo da tela de veículos
            veiculoFiltroId: null,
            mostrandoFormulario: false,
            editandoId: null,
            carregando: true,
            mensagem: null,
            erros: {},
            form: {
                veiculo_id: null,
                data_revisao: '',
                quilometragem: null,
                valor: '',
                descricao: '',
                observacoes: '',
            },
        };
    },

    mounted() {
        // ?veiculo_id= na URL, vindo do link "Ver revisões"
        this.veiculoFiltroId = this.$route.query.veiculo_id || null;

        this.carregarRevisoes();
        this.carregarVeiculos();
    },

    computed: {
        veiculoFiltro() {
            const veiculo = this.revisoes.length > 0 ? this.revisoes[0].veiculo : null;
            return veiculo ? veiculo.marca + ' ' + veiculo.modelo + ' (' + veiculo.placa + ')' : null;
        },
    },

    methods: {
        async carregarRevisoes() {
            this.carregando = true;

            try {
                const params = this.veiculoFiltroId ? { veiculo_id: this.veiculoFiltroId } : {};
                const resposta = await axios.get('/revisoes', { params });
                this.revisoes = resposta.data.data;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar as revisões.', 'erro');
            } finally {
                this.carregando = false;
            }
        },

        // Veículos para o select do formulário
        async carregarVeiculos() {
            try {
                const resposta = await axios.get('/veiculos');
                this.veiculos = resposta.data.data;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar os veículos.', 'erro');
            }
        },

        validaFrontend() {
            this.erros = {};

            if (!this.form.veiculo_id) {
                this.erros.veiculo_id = ['Selecione o veículo.'];
            }
            if (!this.form.data_revisao) {
                this.erros.data_revisao = ['Informe a data da revisão.'];
            }
            if (!this.form.descricao.trim()) {
                this.erros.descricao = ['Informe a descrição.'];
            }
            if (this.form.valor === '' || Number(this.form.valor) < 0) {
                this.erros.valor = ['Informe um valor válido.'];
            }

            return Object.keys(this.erros).length === 0;
        },

        abrirFormulario() {
            // Ao cadastrar vindo da tela de veículos, o veículo já vem selecionado
            if (this.veiculoFiltroId && !this.editandoId) {
                this.form.veiculo_id = this.veiculoFiltroId;
            }
            this.mostrandoFormulario = true;
        },

        cancelar() {
            this.mostrandoFormulario = false;
            this.editandoId = null;
            this.erros = {};
            this.form = {
                veiculo_id: null,
                data_revisao: '',
                quilometragem: null,
                valor: '',
                descricao: '',
                observacoes: '',
            };
        },

        editar(revisao) {
            this.editandoId = revisao.id;
            this.mostrandoFormulario = true;
            this.form = {
                veiculo_id: revisao.veiculo_id,
                data_revisao: revisao.data_revisao,
                quilometragem: revisao.quilometragem,
                valor: revisao.valor,
                descricao: revisao.descricao,
                observacoes: revisao.observacoes || '',
            };
        },

        async salvar() {
            if (!this.validaFrontend()) {
                return;
            }

            try {
                if (this.editandoId) {
                    await axios.put('/revisoes/' + this.editandoId, this.form);
                    this.mostrarMensagem('Revisão atualizada com sucesso.');
                } else {
                    await axios.post('/revisoes', this.form);
                    this.mostrarMensagem('Revisão cadastrada com sucesso.');
                }

                this.cancelar();
                this.carregarRevisoes();
            } catch (erro) {
                if (erro.response && erro.response.status === 422) {
                    this.erros = erro.response.data.errors;
                    this.mostrarMensagem(erro.response.data.message, 'erro');
                } else {
                    const mensagem = erro.response && erro.response.data
                        ? erro.response.data.message
                        : 'Erro inesperado. Tente novamente.';
                    this.mostrarMensagem(mensagem, 'erro');
                }
            }
        },

        async excluir(revisao) {
            if (!confirm('Excluir a revisão de ' + this.formatarData(revisao.data_revisao) + '?')) {
                return;
            }

            try {
                await axios.delete('/revisoes/' + revisao.id);
                this.mostrarMensagem('Revisão excluída com sucesso.');
                this.carregarRevisoes();
            } catch (erro) {
                const mensagem = erro.response && erro.response.data
                    ? erro.response.data.message
                    : 'Erro inesperado. Tente novamente.';
                this.mostrarMensagem(mensagem, 'erro');
            }
        },

        mostrarMensagem(texto, tipo = 'sucesso') {
            this.mensagem = { texto, tipo };
            setTimeout(() => {
                this.mensagem = null;
            }, 4000);
        },

        formatarData(data) {
            if (!data) {
                return '';
            }
            return new Date(data).toLocaleDateString('pt-BR');
        },

        formatarMoeda(valor) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(valor));
        },

        formatarNumero(numero) {
            return new Intl.NumberFormat('pt-BR').format(Number(numero));
        },
    },
};
</script>
