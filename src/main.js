import { createApp, watch } from 'vue'
import router from './router'
import { createPinia } from 'pinia'
import piniaPluginPersistedState from 'pinia-plugin-persistedstate'
import { useSessionStore } from './stores/session'
import { useSingleplayerStore } from './stores/singleplayer'
import App from './App.vue'
import '../node_modules/bootstrap/dist/css/bootstrap.css'

const pinia = createPinia();
pinia.use(piniaPluginPersistedState)
const app = createApp(App);

app.use(pinia);
app.use(router);

// Alt, noch bevor piniaPluginPersistedState genutzt worden ist
// watch(pinia.state, (state) => {
//     localStorage.setItem("loggedIn", JSON.stringify(state.loggedIn));
//     localStorage.setItem("userID", JSON.stringify(state.userID));
// },
// { deep:true });

app.mount('#app');

// const sessionStore = useSessionStore()
// const singleplayerStore = useSingleplayerStore()
// // createApp(App).mount('#app');
