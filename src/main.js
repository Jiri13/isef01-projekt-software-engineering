import { createApp, watch } from 'vue'
import router from './router'
import { createPinia } from 'pinia'
import piniaPluginPersistedState from 'pinia-plugin-persistedstate'
import { useSessionStore } from './stores/session'
import App from './App.vue'
import '../node_modules/bootstrap/dist/css/bootstrap.css'

const pinia = createPinia();
pinia.use(piniaPluginPersistedState)
const app = createApp(App);

app.use(pinia);
app.use(router);

watch(pinia.state, (state) => {
    localStorage.setItem("loggedIn", JSON.stringify(state.loggedIn));
},
{ deep:true });

app.mount('#app');

const sessionStore = useSessionStore()
// createApp(App).mount('#app');
