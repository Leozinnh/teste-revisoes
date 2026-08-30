import Vue from 'vue';
import VueRouter from 'vue-router';

import Dashboard from './components/Dashboard.vue';
import Pessoas from './components/Pessoas.vue';
import Veiculos from './components/Veiculos.vue';
import Revisoes from './components/Revisoes.vue';
import Relatorios from './components/Relatorios.vue';

Vue.use(VueRouter);

const router = new VueRouter({
    mode: 'history',
    routes: [
        { path: '/', component: Dashboard, meta: { titulo: 'Dashboard' } },
        { path: '/pessoas', component: Pessoas, meta: { titulo: 'Pessoas' } },
        { path: '/veiculos', component: Veiculos, meta: { titulo: 'Veículos' } },
        { path: '/revisoes', component: Revisoes, meta: { titulo: 'Revisões' } },
        { path: '/relatorios', component: Relatorios, meta: { titulo: 'Relatórios' } },
    ],
});

// Título da aba do navegador acompanha a página atual
router.afterEach((rota) => {
    document.title = (rota.meta.titulo || 'AutoCare') + ' · AutoCare';
});

export default router;
