import axios from 'axios';

// Laravel exige o token CSRF em requisições POST/PUT/DELETE
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content;

export default axios;
