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

        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-700">
                {{ editandoId ? 'Editando pessoa' : 'Lista de pessoas' }}
            </h2>
            <button
                v-if="!mostrandoFormulario"
                @click="abrirFormulario()"
                class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700"
            >
                + Nova pessoa
            </button>
        </div>

        <!-- Formulário (cadastro e edição) -->
        <div v-if="mostrandoFormulario" class="mb-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Nome *</label>
                    <input
                        v-model="form.nome"
                        type="text"
                        maxlength="255"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.nome ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.nome" class="mt-1 text-xs text-red-600">{{ erros.nome[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">CPF *</label>
                    <input
                        v-model="form.cpf"
                        type="text"
                        placeholder="000.000.000-00"
                        maxlength="14"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.cpf ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.cpf" class="mt-1 text-xs text-red-600">{{ erros.cpf[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Sexo *</label>
                    <select
                        v-model="form.sexo"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.sexo ? 'border-red-400' : 'border-slate-300'"
                    >
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                    </select>
                    <p v-if="erros.sexo" class="mt-1 text-xs text-red-600">{{ erros.sexo[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Data de nascimento *</label>
                    <input
                        v-model="form.data_nascimento"
                        type="date"
                        min="1900-01-01"
                        :max="hoje"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.data_nascimento ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.data_nascimento" class="mt-1 text-xs text-red-600">{{ erros.data_nascimento[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Telefone *</label>
                    <input
                        v-model="form.telefone"
                        type="text"
                        placeholder="(11) 99999-9999"
                        maxlength="20"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.telefone ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.telefone" class="mt-1 text-xs text-red-600">{{ erros.telefone[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">E-mail *</label>
                    <input
                        v-model="form.email"
                        type="email"
                        maxlength="255"
                        class="w-full rounded-lg border px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200"
                        :class="erros.email ? 'border-red-400' : 'border-slate-300'"
                    >
                    <p v-if="erros.email" class="mt-1 text-xs text-red-600">{{ erros.email[0] }}</p>
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
                        <th class="px-4 py-3">Nome</th>
                        <th class="px-4 py-3">CPF</th>
                        <th class="px-4 py-3">Sexo</th>
                        <th class="px-4 py-3">Nascimento</th>
                        <th class="px-4 py-3">E-mail</th>
                        <th class="px-4 py-3">Veículos</th>
                        <th class="px-4 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="pessoa in pessoas" :key="pessoa.id" class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ pessoa.nome }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ formatarCPF(pessoa.cpf) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ pessoa.sexo === 'M' ? 'Masculino' : 'Feminino' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ formatarData(pessoa.data_nascimento) }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ pessoa.email }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ pessoa.veiculos_count }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <router-link
                                :to="'/veiculos?pessoa_id=' + pessoa.id"
                                class="mr-3 text-sky-600 hover:underline"
                            >
                                Ver veículos
                            </router-link>
                            <button @click="editar(pessoa)" class="mr-3 text-sky-600 hover:underline">Editar</button>
                            <button @click="excluir(pessoa)" class="text-red-600 hover:underline">Excluir</button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p v-if="carregando" class="px-4 py-6 text-center text-sm text-slate-500">Carregando...</p>
            <p v-else-if="pessoas.length === 0" class="px-4 py-6 text-center text-sm text-slate-500">
                Nenhuma pessoa cadastrada ainda.
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
    name: 'Pessoas',

    data() {
        return {
            pessoas: [],
            pagina: 1,
            paginacao: {},
            mostrandoFormulario: false,
            editandoId: null,
            carregando: true,
            mensagem: null,
            erros: {},
            hoje: new Date().toISOString().slice(0, 10),
            form: {
                nome: '',
                cpf: '',
                sexo: 'M',
                data_nascimento: '',
                telefone: '',
                email: '',
            },
        };
    },

    mounted() {
        this.carregarPessoas();
    },

    methods: {
        async carregarPessoas() {
            this.carregando = true;

            try {
                const resposta = await axios.get('/pessoas', { params: { page: this.pagina } });
                this.pessoas = resposta.data.data;
                this.paginacao = resposta.data.meta;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível carregar as pessoas.', 'erro');
            } finally {
                this.carregando = false;
            }
        },

        mudarPagina(pagina) {
            if (pagina < 1 || pagina > (this.paginacao.last_page || 1)) {
                return;
            }
            this.pagina = pagina;
            this.carregarPessoas();
        },

        // Validação simples; o backend (Form Request) valida de novo
        validaFrontend() {
            this.erros = {};

            if (!this.form.nome.trim()) {
                this.erros.nome = ['Informe o nome.'];
            }
            if (!this.form.cpf.trim()) {
                this.erros.cpf = ['Informe o CPF.'];
            }
            if (!this.form.sexo) {
                this.erros.sexo = ['Selecione o sexo.'];
            }
            if (!this.form.data_nascimento) {
                this.erros.data_nascimento = ['Informe a data de nascimento.'];
            } else if (this.form.data_nascimento >= this.hoje) {
                this.erros.data_nascimento = ['A data de nascimento deve ser anterior a hoje.'];
            }
            const digitosTelefone = this.form.telefone.replace(/\D/g, '');
            if (digitosTelefone.length < 10 || digitosTelefone.length > 11) {
                this.erros.telefone = ['Informe o telefone com DDD (10 ou 11 dígitos).'];
            }
            if (!this.form.email.includes('@')) {
                this.erros.email = ['Informe um e-mail válido.'];
            }

            return Object.keys(this.erros).length === 0;
        },

        abrirFormulario() {
            this.mostrandoFormulario = true;
        },

        cancelar() {
            this.mostrandoFormulario = false;
            this.editandoId = null;
            this.erros = {};
            this.form = {
                nome: '',
                cpf: '',
                sexo: 'M',
                data_nascimento: '',
                telefone: '',
                email: '',
            };
        },

        editar(pessoa) {
            this.editandoId = pessoa.id;
            this.mostrandoFormulario = true;
            this.form = {
                nome: pessoa.nome,
                cpf: pessoa.cpf,
                sexo: pessoa.sexo,
                data_nascimento: pessoa.data_nascimento,
                telefone: pessoa.telefone,
                email: pessoa.email,
            };
        },

        async salvar() {
            if (!this.validaFrontend()) {
                return;
            }

            try {
                if (this.editandoId) {
                    await axios.put('/pessoas/' + this.editandoId, this.form);
                    this.mostrarMensagem('Pessoa atualizada com sucesso.');
                } else {
                    await axios.post('/pessoas', this.form);
                    this.mostrarMensagem('Pessoa cadastrada com sucesso.');
                }

                this.cancelar();
                this.carregarPessoas();
            } catch (erro) {
                // Erro 422 = validação do backend: mostra os erros nos campos
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

        async excluir(pessoa) {
            if (!confirm('Excluir a pessoa "' + pessoa.nome + '"?')) {
                return;
            }

            try {
                await axios.delete('/pessoas/' + pessoa.id);
                this.mostrarMensagem('Pessoa excluída com sucesso.');

                // Se era o último item da última página, volta uma página
                if (this.pessoas.length === 1 && this.pagina > 1) {
                    this.pagina--;
                }
                this.carregarPessoas();
            } catch (erro) {
                const mensagem = erro.response && erro.response.data
                    ? erro.response.data.message
                    : 'Erro inesperado. Tente novamente.';
                this.mostrarMensagem(mensagem, 'erro');
            }
        },

        mostrarMensagem(texto, tipo = 'sucesso') {
            this.mensagem = { texto, tipo };
            // A mensagem some sozinha após 4 segundos
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

        formatarCPF(cpf) {
            if (!cpf) {
                return '';
            }
            // O banco guarda só dígitos; aqui só entra com 11 dígitos
            const d = cpf.replace(/\D/g, '');
            if (d.length !== 11) {
                return cpf;
            }
            return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        },
    },
};
</script>
