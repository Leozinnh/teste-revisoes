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

        <!-- Aviso de filtro vindo da tela de pessoas -->
        <div
            v-if="pessoaFiltro"
            class="mb-6 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700"
        >
            Mostrando veículos de <strong>{{ pessoaFiltro }}</strong>.
            <router-link to="/veiculos" class="ml-2 font-medium underline">Ver todos</router-link>
        </div>

        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-700">
                {{ editandoId ? 'Editando veículo' : 'Lista de veículos' }}
            </h2>
            <button
                v-if="!mostrandoFormulario"
                @click="abrirFormulario()"
                class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700"
            >
                + Novo veículo
            </button>
        </div>

        <!-- Formulário (cadastro e edição) -->
        <div v-if="mostrandoFormulario" class="mb-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Proprietário *</label>
                    <select
                        v-model="form.pessoa_id"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.pessoa_id ? 'border-red-400' : 'border-slate-300'"
                    >
                        <option :value="null" disabled>Selecione a pessoa</option>
                        <option v-for="pessoa in pessoas" :key="pessoa.id" :value="pessoa.id">
                            {{ pessoa.nome }}
                        </option>
                    </select>
                    <p v-if="erros.pessoa_id" class="mt-1 text-xs text-red-600">{{ erros.pessoa_id[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Marca *</label>
                    <input
                        v-model="form.marca"
                        type="text"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Modelo *</label>
                    <input
                        v-model="form.modelo"
                        type="text"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Ano *</label>
                    <input
                        v-model.number="form.ano"
                        type="number"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Placa *</label>
                    <input
                        v-model="form.placa"
                        type="text"
                        placeholder="ABC-1234"
                        class="w-full rounded-lg border px-3 py-2 text-sm uppercase outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.placa ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.placa" class="mt-1 text-xs text-red-600">{{ erros.placa[0] }}</p>
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
                        <th class="px-4 py-3">Proprietário</th>
                        <th class="px-4 py-3">Marca</th>
                        <th class="px-4 py-3">Modelo</th>
                        <th class="px-4 py-3">Ano</th>
                        <th class="px-4 py-3">Placa</th>
                        <th class="px-4 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="veiculo in veiculos" :key="veiculo.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ veiculo.pessoa.nome }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ veiculo.marca }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ veiculo.modelo }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ veiculo.ano }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ veiculo.placa }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <router-link
                                :to="'/revisoes?veiculo_id=' + veiculo.id"
                                class="mr-3 text-sky-600 hover:underline"
                            >
                                Ver revisões
                            </router-link>
                            <button @click="editar(veiculo)" class="mr-3 text-sky-600 hover:underline">Editar</button>
                            <button @click="excluir(veiculo)" class="text-red-600 hover:underline">Excluir</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p v-if="carregando" class="px-4 py-6 text-center text-sm text-slate-500">Carregando...</p>
            <p v-else-if="veiculos.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">
                Nenhum veículo cadastrado ainda.
            </p>
        </div>

    </div>
</template>

<script>
import axios from '../bootstrap';

export default {
    name: 'Veiculos',

    data() {
        return {
            veiculos: [],
            pessoas: [],
            // pessoa filtrada pela URL (?pessoa_id=), vinda da tela de pessoas
            pessoaFiltroId: null,
            mostrandoFormulario: false,
            editandoId: null,
            carregando: true,
            mensagem: null,
            erros: {},
            form: {
                pessoa_id: null,
                marca: '',
                modelo: '',
                ano: null,
                placa: '',
            },
        };
    },

    mounted() {
        // ?pessoa_id= na URL, vindo do link "Ver veículos"
        this.pessoaFiltroId = this.$route.query.pessoa_id || null;

        this.carregarVeiculos();
        this.carregarPessoas();
    },

    computed: {
        pessoaFiltro() {
            const pessoa = this.veiculos.length > 0 ? this.veiculos[0].pessoa : null;
            return pessoa ? pessoa.nome : null;
        },
    },

    methods: {
        async carregarVeiculos() {
            this.carregando = true;

            try {
                const params = this.pessoaFiltroId ? { pessoa_id: this.pessoaFiltroId } : {};
                const resposta = await axios.get('/veiculos', { params });
                this.veiculos = resposta.data.data;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar os veículos.', 'erro');
            } finally {
                this.carregando = false;
            }
        },

        // Pessoas para o select do formulário
        async carregarPessoas() {
            try {
                const resposta = await axios.get('/pessoas');
                this.pessoas = resposta.data.data;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar as pessoas.', 'erro');
            }
        },

        validaFrontend() {
            this.erros = {};

            if (!this.form.pessoa_id) {
                this.erros.pessoa_id = ['Selecione o proprietário.'];
            }
            if (!this.form.marca.trim()) {
                this.erros.marca = ['Informe a marca.'];
            }
            if (!this.form.modelo.trim()) {
                this.erros.modelo = ['Informe o modelo.'];
            }
            if (!this.form.ano) {
                this.erros.ano = ['Informe o ano.'];
            }
            if (!this.form.placa.trim()) {
                this.erros.placa = ['Informe a placa.'];
            }

            return Object.keys(this.erros).length === 0;
        },

        abrirFormulario() {
            // Ao cadastrar vindo da tela de pessoas, o proprietário
            // já vem selecionado
            if (this.pessoaFiltroId && !this.editandoId) {
                this.form.pessoa_id = this.pessoaFiltroId;
            }
            this.mostrandoFormulario = true;
        },

        cancelar() {
            this.mostrandoFormulario = false;
            this.editandoId = null;
            this.erros = {};
            this.form = {
                pessoa_id: null,
                marca: '',
                modelo: '',
                ano: null,
                placa: '',
            };
        },

        editar(veiculo) {
            this.editandoId = veiculo.id;
            this.mostrandoFormulario = true;
            this.form = {
                pessoa_id: veiculo.pessoa_id,
                marca: veiculo.marca,
                modelo: veiculo.modelo,
                ano: veiculo.ano,
                placa: veiculo.placa,
            };
        },

        async salvar() {
            if (!this.validaFrontend()) {
                return;
            }

            try {
                if (this.editandoId) {
                    await axios.put('/veiculos/' + this.editandoId, this.form);
                    this.mostrarMensagem('Veículo atualizado com sucesso.');
                } else {
                    await axios.post('/veiculos', this.form);
                    this.mostrarMensagem('Veículo cadastrado com sucesso.');
                }

                this.cancelar();
                this.carregarVeiculos();
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

        async excluir(veiculo) {
            if (!confirm('Excluir o veículo "' + veiculo.marca + ' ' + veiculo.modelo + ' (' + veiculo.placa + ')"?')) {
                return;
            }

            try {
                await axios.delete('/veiculos/' + veiculo.id);
                this.mostrarMensagem('Veículo excluído com sucesso.');
                this.carregarVeiculos();
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
    },
};
</script>
