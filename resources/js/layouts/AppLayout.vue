<template>
    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-10 flex w-64 flex-col bg-slate-900">
            <div class="flex items-center border-b border-slate-800 px-6 py-5">
                <span class="text-xl font-bold text-sky-400">Auto<span class="text-white">Care</span></span>
            </div>

            <nav class="flex-1 space-y-1 px-3 py-4">
                <router-link
                    v-for="item in itensMenu"
                    :key="item.rota"
                    :to="item.rota"
                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm"
                    :class="item.rota === rotaAtiva ? 'bg-sky-600 text-white' : 'text-slate-300 hover:bg-slate-800'"
                >
                    {{ item.titulo }}
                </router-link>
            </nav>

            <div class="border-t border-slate-800 px-6 py-4 text-xs text-slate-500">
                Leonardo Alves
            </div>
        </aside>

        <!-- Área principal -->
        <main class="ml-64 flex-1">

            <header class="border-b border-slate-200 bg-white px-8 py-5">
                <h1 class="text-xl font-semibold text-slate-800">{{ tituloPagina }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">{{ subtituloPagina }}</p>
            </header>

            <div class="p-8">
                <router-view></router-view>
            </div>

        </main>
    </div>
</template>

<script>
export default {
    name: 'AppLayout',

    data() {
        return {
            // Itens do menu; título e subtítulo do cabeçalho vêm daqui
            itensMenu: [
                { rota: '/', titulo: 'Dashboard', subtitulo: 'Visão geral do sistema' },
                { rota: '/pessoas', titulo: 'Pessoas', subtitulo: 'Cadastro de proprietários de veículos' },
                { rota: '/veiculos', titulo: 'Veículos', subtitulo: 'Cadastro de veículos vinculados a uma pessoa' },
                { rota: '/revisoes', titulo: 'Revisões', subtitulo: 'Histórico de revisões dos veículos' },
                { rota: '/relatorios', titulo: 'Relatórios', subtitulo: 'Consultas e indicadores do sistema' },
                { rota: '/manutencao', titulo: 'Manutenção', subtitulo: 'Apagar o banco e popular de novo (protegido por token)' },
            ],
        };
    },

    computed: {
        // Caminho "de primeiro nível" da rota atual, ex.: "/pessoas"
        rotaAtiva() {
            return '/' + this.$route.path.split('/')[1];
        },

        paginaAtual() {
            return this.itensMenu.find((item) => item.rota === this.rotaAtiva);
        },

        tituloPagina() {
            return this.paginaAtual.titulo;
        },

        subtituloPagina() {
            return this.paginaAtual.subtitulo;
        },
    },
};
</script>
