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
                        min="1900-01-01"
                        :max="hoje"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.data_revisao ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.data_revisao" class="mt-1 text-xs text-red-600">{{ erros.data_revisao[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Quilometragem (km) *</label>
                    <input
                        v-model.number="form.quilometragem"
                        type="number"
                        min="0"
                        max="2000000"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.quilometragem ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.quilometragem" class="mt-1 text-xs text-red-600">{{ erros.quilometragem[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Valor (R$) *</label>
                    <input
                        v-model="form.valor"
                        type="text"
                        inputmode="decimal"
                        placeholder="0,00"
                        maxlength="15"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.valor ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.valor" class="mt-1 text-xs text-red-600">{{ erros.valor[0] }}</p>
                    <p class="mt-1 text-xs text-slate-400">Use vírgula para os centavos: ex.: 1.234,56</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Descrição *</label>
                    <input
                        v-model="form.descricao"
                        type="text"
                        placeholder="Ex.: troca de óleo e filtros"
                        maxlength="255"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.descricao ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.descricao" class="mt-1 text-xs text-red-600">{{ erros.descricao[0] }}</p>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-600">Observações</label>
                    <textarea
                        v-model="form.observacoes"
                        rows="3"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.observacoes ? 'border-red-400' : 'border-slate-300'"
                    ></textarea>
                    <p v-if="erros.observacoes" class="mt-1 text-xs text-red-600">{{ erros.observacoes[0] }}</p>
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
                {{ veiculoFiltro
                    ? 'Este veículo não possui revisões cadastradas.'
                    : 'Nenhuma revisão cadastrada ainda.' }}
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
    name: 'Revisoes',

    data() {
        return {
            revisoes: [],
            veiculos: [],
            pagina: 1,
            paginacao: {},
            // veículo filtrado pela URL (?veiculo_id=), vindo da tela de veículos
            veiculoFiltroId: null,
            // nome do veículo do filtro, buscado à parte: com a paginação,
            // ele nem sempre está na lista da página
            veiculoFiltroNome: null,
            mostrandoFormulario: false,
            editandoId: null,
            carregando: true,
            mensagem: null,
            erros: {},
            hoje: new Date().toISOString().slice(0, 10),
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

    computed: {
        veiculoFiltro() {
            return this.veiculoFiltroId ? this.veiculoFiltroNome : null;
        },
    },

    mounted() {
        this.aplicarFiltroDaUrl();
        this.carregarRevisoes();
        this.carregarVeiculos();
    },

    watch: {
        // "Ver todas" só troca a query da URL e o Vue reaproveita o
        // componente — sem esse watch o filtro não seria relido
        '$route.query'() {
            this.aplicarFiltroDaUrl();
            this.carregarRevisoes();
        },
    },

    methods: {
        aplicarFiltroDaUrl() {
            this.veiculoFiltroId = this.$route.query.veiculo_id || null;
            this.veiculoFiltroNome = null;
            this.pagina = 1;
            this.carregarNomeDoFiltro();
        },

        // Busca a descrição do veículo filtrado (endpoint show)
        async carregarNomeDoFiltro() {
            if (!this.veiculoFiltroId) {
                return;
            }

            try {
                const resposta = await axios.get('/veiculos/' + this.veiculoFiltroId);
                const veiculo = resposta.data.data;
                this.veiculoFiltroNome = veiculo.marca + ' ' + veiculo.modelo + ' (' + veiculo.placa + ')';
            } catch (erro) {
                // Veículo excluído entre a navegação e o carregamento: some o aviso
                this.veiculoFiltroNome = null;
            }
        },

        async carregarRevisoes() {
            this.carregando = true;

            try {
                const params = { page: this.pagina };
                if (this.veiculoFiltroId) {
                    params.veiculo_id = this.veiculoFiltroId;
                }
                const resposta = await axios.get('/revisoes', { params });
                this.revisoes = resposta.data.data;
                this.paginacao = resposta.data.meta;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar as revisões.', 'erro');
            } finally {
                this.carregando = false;
            }
        },

        // Select do formulário: per_page=500 pra vir a lista inteira,
        // não só os 25 da primeira página
        async carregarVeiculos() {
            try {
                const resposta = await axios.get('/veiculos', { params: { per_page: 500 } });
                this.veiculos = resposta.data.data;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar os veículos.', 'erro');
            }
        },

        mudarPagina(pagina) {
            if (pagina < 1 || pagina > (this.paginacao.last_page || 1)) {
                return;
            }
            this.pagina = pagina;
            this.carregarRevisoes();
        },

        // O usuário digita "1.234,56" e a API só aceita ponto:
        // tira os pontos de milhar e troca a vírgula na hora de enviar
        converterValorParaEnvio(valor) {
            if (valor === null || valor === undefined) {
                return '';
            }
            return String(valor).trim().replace(/\./g, '').replace(',', '.');
        },

        validaFrontend() {
            this.erros = {};

            if (!this.form.veiculo_id) {
                this.erros.veiculo_id = ['Selecione o veículo.'];
            }
            if (!this.form.data_revisao) {
                this.erros.data_revisao = ['Informe a data da revisão.'];
            } else if (this.form.data_revisao > this.hoje) {
                this.erros.data_revisao = ['A data da revisão não pode ser no futuro.'];
            }
            if (this.form.quilometragem === null || this.form.quilometragem === '') {
                this.erros.quilometragem = ['Informe a quilometragem.'];
            } else if (this.form.quilometragem < 0) {
                this.erros.quilometragem = ['A quilometragem não pode ser negativa.'];
            } else if (this.form.quilometragem > 2000000) {
                this.erros.quilometragem = ['A quilometragem não pode ultrapassar 2.000.000 km.'];
            }

            const valorTexto = this.form.valor === null || this.form.valor === undefined
                ? ''
                : String(this.form.valor).trim();
            // Formato brasileiro: "350", "350,50" ou "1.234,56"
            const valorValido = /^\d{1,3}(\.\d{3})*(,\d{1,2})?$/.test(valorTexto);
            if (!valorTexto) {
                this.erros.valor = ['Informe o valor.'];
            } else if (!valorValido) {
                this.erros.valor = ['Informe o valor no formato brasileiro (ex.: 1.234,56).'];
            } else {
                const valorEnviado = Number(this.converterValorParaEnvio(valorTexto));
                if (valorEnviado > 99999999.99) {
                    this.erros.valor = ['O valor não pode ultrapassar R$ 99.999.999,99.'];
                }
            }

            if (!this.form.descricao.trim()) {
                this.erros.descricao = ['Informe a descrição.'];
            }

            return Object.keys(this.erros).length === 0;
        },

        abrirFormulario() {
            // Ao cadastrar vindo da tela de veículos, o veículo já vem selecionado
            if (this.veiculoFiltroId && !this.editandoId) {
                this.form.veiculo_id = Number(this.veiculoFiltroId);
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
                // O banco devolve "1234.56"; o input mostra "1.234,56"
                valor: revisao.valor === null || revisao.valor === undefined
                    ? ''
                    : new Intl.NumberFormat('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }).format(Number(revisao.valor)),
                descricao: revisao.descricao,
                observacoes: revisao.observacoes || '',
            };
        },

        async salvar() {
            if (!this.validaFrontend()) {
                return;
            }

            // Valor digitado em formato brasileiro vira ponto decimal no envio
            const dados = Object.assign({}, this.form);
            dados.valor = this.converterValorParaEnvio(dados.valor);
            dados.observacoes = dados.observacoes || null;

            try {
                if (this.editandoId) {
                    await axios.put('/revisoes/' + this.editandoId, dados);
                    this.mostrarMensagem('Revisão atualizada com sucesso.');
                } else {
                    await axios.post('/revisoes', dados);
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

                // Se era o último item da última página, volta uma página
                if (this.revisoes.length === 1 && this.pagina > 1) {
                    this.pagina--;
                }
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
