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

        <div class="max-w-2xl rounded-xl border border-red-200 bg-white p-6 shadow-sm">

            <h3 class="font-semibold text-red-700">Apagar tudo e popular de novo</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                Apaga <strong>todas</strong> as pessoas, veículos e revisões, recria as
                tabelas pelas migrations e insere os dados de demonstração do seeder.
                Serve para descartar dados inválidos (ex.: as placas antigas com
                <code class="rounded bg-slate-100 px-1">####</code> geradas pelo seeder com bug).
                Não tem volta.
            </p>

            <!-- Painel desligado: token não configurado -->
            <div
                v-if="!carregandoStatus && !tokenConfigurado"
                class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
            >
                O painel está <strong>desligado</strong>: defina o
                <code class="rounded bg-white px-1">MANUTENCAO_TOKEN</code> no
                <code class="rounded bg-white px-1">.env</code> (um valor difícil de adivinhar),
                suba o código e rode <code class="rounded bg-white px-1">php artisan config:clear</code>
                se houver cache de configuração.
            </div>

            <div v-if="!carregandoStatus && tokenConfigurado" class="mt-5 space-y-4">

                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input
                        v-model="comVolume"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-slate-300 accent-sky-600"
                    >
                    <span>
                        Incluir também os <strong>dados em volume</strong> para os relatórios
                        (~200 pessoas, ~360 veículos e 1.000+ revisões — demora mais alguns segundos)
                    </span>
                </label>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Token de manutenção *</label>
                    <input
                        v-model="token"
                        type="password"
                        autocomplete="off"
                        placeholder="MANUTENCAO_TOKEN definido no .env"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-200 sm:max-w-xs"
                    >
                </div>

                <button
                    @click="limpar()"
                    :disabled="processando"
                    class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ processando ? 'Apagando e populando...' : 'Apagar tudo e popular' }}
                </button>

                <p v-if="processando" class="text-sm text-slate-500">
                    Isso leva alguns segundos (mais com o volume). Não feche a página.
                </p>

                <!-- Resultado com as contagens -->
                <div
                    v-if="resultado"
                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                >
                    <p class="font-medium">Pronto! Banco recriado e populado:</p>
                    <ul class="mt-1 list-inside list-disc">
                        <li>{{ resultado.contagem.pessoas }} pessoas</li>
                        <li>{{ resultado.contagem.veiculos }} veículos</li>
                        <li>{{ resultado.contagem.revisoes }} revisões</li>
                    </ul>
                    <p class="mt-1 text-xs text-emerald-600">
                        Etapas executadas: {{ resultado.etapas.join(' → ') }}
                    </p>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import axios from '../bootstrap';

export default {
    name: 'Manutencao',

    data() {
        return {
            carregandoStatus: true,
            tokenConfigurado: false,
            token: '',
            comVolume: false,
            processando: false,
            resultado: null,
            mensagem: null,
        };
    },

    mounted() {
        this.verificarStatus();
    },

    methods: {
        async verificarStatus() {
            try {
                const resposta = await axios.get('/manutencao');
                this.tokenConfigurado = resposta.data.token_configurado;
            } catch (erro) {
                this.mostrarMensagem('Não foi possível consultar o painel de manutenção.', 'erro');
            } finally {
                this.carregandoStatus = false;
            }
        },

        async limpar() {
            if (!this.token.trim()) {
                this.mostrarMensagem('Informe o token de manutenção.', 'erro');
                return;
            }

            if (!confirm(
                'Isso apaga TODOS os dados (pessoas, veículos e revisões) e popula de novo. Não tem volta. Continuar?'
            )) {
                return;
            }

            this.processando = true;
            this.resultado = null;
            this.mensagem = null;

            try {
                const resposta = await axios.post('/manutencao/limpar', {
                    token: this.token.trim(),
                    com_volume: this.comVolume,
                });
                this.resultado = resposta.data;
                this.mostrarMensagem(resposta.data.message);
            } catch (erro) {
                const mensagem = erro.response && erro.response.data
                    ? erro.response.data.message
                    : 'Erro inesperado. Tente novamente.';
                this.mostrarMensagem(mensagem, 'erro');
            } finally {
                this.processando = false;
            }
        },

        mostrarMensagem(texto, tipo = 'sucesso') {
            this.mensagem = { texto, tipo };
            setTimeout(() => {
                this.mensagem = null;
            }, 6000);
        },
    },
};
</script>
