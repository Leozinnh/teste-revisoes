import Vue from 'vue';
import AppLayout from './layouts/AppLayout.vue';
import router from './router';

new Vue({
    router,
    render: (h) => h(AppLayout),
}).$mount('#app');
