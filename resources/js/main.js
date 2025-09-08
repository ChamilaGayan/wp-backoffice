import { createApp } from 'vue';
import App from './App.vue';
import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const app = createApp(App);
app.provide('axios', axios);
app.mount('#app');
