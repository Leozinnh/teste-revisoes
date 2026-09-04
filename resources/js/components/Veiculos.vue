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

        <!-- Busca por placa, marca, modelo ou proprietário -->
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center">
            <input
                v-model="busca"
                type="text"
                placeholder="Buscar por placa, marca, modelo ou proprietário..."
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200 sm:max-w-xs"
                @keyup.enter="buscar()"
            >
            <div class="flex gap-2">
                <button
                    @click="buscar()"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Buscar
                </button>
                <button
                    v-if="buscaAplicada"
                    @click="limparBusca()"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Limpar
                </button>
            </div>
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
                        maxlength="100"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.marca ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.marca" class="mt-1 text-xs text-red-600">{{ erros.marca[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Modelo *</label>
                    <input
                        v-model="form.modelo"
                        type="text"
                        maxlength="100"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.modelo ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.modelo" class="mt-1 text-xs text-red-600">{{ erros.modelo[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Ano *</label>
                    <input
                        v-model.number="form.ano"
                        type="number"
                        min="1900"
                        max="2100"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.ano ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.ano" class="mt-1 text-xs text-red-600">{{ erros.ano[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Placa *</label>
                    <input
                        v-model="form.placa"
                        type="text"
                        placeholder="ABC-1234"
                        maxlength="8"
                        class="w-full rounded-lg border px-3 py-2 text-sm uppercase outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.placa ? 'border-red-400' : 'border-slate-300'"
                        @input="form.placa = form.placa.toUpperCase()"
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
                {{ mensagemListaVazia }}
            </p>

            <!-- Rodapé de paginação -->
            <div
                v-if="!carregando && paginacao.total > 0"
                class="flex items-center justify-between border-t border-slate-200 px-4 py-3"
            >
                <p class="text-sm text-slate-500">
                    Página {{ paginacao.current_page }} de {{ paginacao.last_page }}
                    ({{ paginacao.total }} no total)
                </p>
                <div class="flex gap-2">
                    <button
                        @click="mudarPagina(paginacao.current_page - 1)"
                        :disabled="paginacao.current_page <= 1"
                        class="rounded-lg border border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Anterior
                    </button>
                    <button
                        @click="mudarPagina(paginacao.current_page + 1)"
                        :disabled="paginacao.current_page >= paginacao.last_page"
                        class="rounded-lg border border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Próxima
                    </button>
                </div>
            </div>
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
            pagina: 1,
            paginacao: {},
            // texto digitado no campo de busca
            busca: '',
            // último termo que realmente filtrou a lista (o campo pode
            // ser editado sem recarregar até apertar Buscar ou Enter)
            buscaAplicada: '',
            // pessoa filtrada pela URL (?pessoa_id=), vinda da tela de pessoas
            pessoaFiltroId: null,
            // nome da pessoa do filtro, buscado à parte: com a paginação,
            // ela nem sempre está na lista da página
            pessoaFiltroNome: null,
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

    computed: {
        pessoaFiltro() {
            return this.pessoaFiltroId ? this.pessoaFiltroNome : null;
        },

        mensagemListaVazia() {
            if (this.buscaAplicada) {
                return 'Nenhum veículo encontrado para "' + this.buscaAplicada + '".';
            }
            if (this.pessoaFiltro) {
                return 'Este proprietário não possui veículos cadastrados.';
            }
            return 'Nenhum veículo cadastrado ainda.';
        },
    },

    mounted() {
        this.aplicarFiltroDaUrl();
        this.carregarVeiculos();
        this.carregarPessoas();
    },

    watch: {
        // "Ver todos" só troca a query da URL e o Vue reaproveita o
        // componente — sem esse watch o filtro não seria relido
        '$route.query'() {
            this.aplicarFiltroDaUrl();
            this.carregarVeiculos();
        },
    },

    methods: {
        aplicarFiltroDaUrl() {
            this.pessoaFiltroId = this.$route.query.pessoa_id || null;
            this.pessoaFiltroNome = null;
            this.pagina = 1;
            this.carregarNomeDoFiltro();
        },

        // Busca o nome da pessoa filtrada (endpoint show)
        async carregarNomeDoFiltro() {
            if (!this.pessoaFiltroId) {
                return;
            }

            try {
                const resposta = await axios.get('/pessoas/' + this.pessoaFiltroId);
                this.pessoaFiltroNome = resposta.data.data.nome;
            } catch (erro) {
                // Pessoa excluída entre a navegação e o carregamento: some o aviso
                this.pessoaFiltroNome = null;
            }
        },

        async carregarVeiculos() {
            this.carregando = true;

            try {
                const params = { page: this.pagina };
                if (this.pessoaFiltroId) {
                    params.pessoa_id = this.pessoaFiltroId;
                }
                if (this.buscaAplicada) {
                    params.busca = this.buscaAplicada;
                }
                const resposta = await axios.get('/veiculos', { params });
                this.veiculos = resposta.data.data;
                this.paginacao = resposta.data.meta;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar os veículos.', 'erro');
            } finally {
                this.carregando = false;
            }
        },

        // Select do formulário: per_page=500 pra vir a lista inteira,
        // não só os 25 da primeira página
        async carregarPessoas() {
            try {
                const resposta = await axios.get('/pessoas', { params: { per_page: 500 } });
                this.pessoas = resposta.data.data;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar as pessoas.', 'erro');
            }
        },

        mudarPagina(pagina) {
            if (pagina < 1 || pagina > (this.paginacao.last_page || 1)) {
                return;
            }
            this.pagina = pagina;
            this.carregarVeiculos();
        },

        buscar() {
            this.buscaAplicada = this.busca.trim();
            this.pagina = 1;
            this.carregarVeiculos();
        },

        limparBusca() {
            this.busca = '';
            this.buscaAplicada = '';
            this.pagina = 1;
            this.carregarVeiculos();
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
            } else if (this.form.ano < 1900 || this.form.ano > 2100) {
                this.erros.ano = ['O ano deve estar entre 1900 e 2100.'];
            }
            if (!this.form.placa.trim()) {
                this.erros.placa = ['Informe a placa.'];
            } else if (!/^([A-Z]{3}\d{4}|[A-Z]{3}\d[A-Z]\d{2})$/.test(this.form.placa.replace(/[^A-Za-z0-9]/g, ''))) {
                this.erros.placa = ['Formato de placa inválido. Use ABC1234 ou ABC1D23.'];
            }

            return Object.keys(this.erros).length === 0;
        },

        abrirFormulario() {
            // Ao cadastrar vindo da tela de pessoas, o proprietário
            // já vem selecionado
            if (this.pessoaFiltroId && !this.editandoId) {
                this.form.pessoa_id = Number(this.pessoaFiltroId);
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

                // Se era o último item da última página, volta uma página
                if (this.veiculos.length === 1 && this.pagina > 1) {
                    this.pagina--;
                }
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
