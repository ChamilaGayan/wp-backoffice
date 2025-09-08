import { createApp } from 'vue'
import App from './App.vue'
import axios from 'axios'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
const vuetify = createVuetify()

axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const app = createApp(App)
app.provide('axios', axios)
app.use(vuetify)
app.mount('#app')
