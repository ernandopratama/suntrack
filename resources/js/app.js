import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import '@fortawesome/fontawesome-free/css/all.min.css';
import { useAuthStore } from './stores/auth';
import { useThemeStore } from './stores/theme';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

const authStore = useAuthStore(pinia);
app.config.globalProperties.$can = (permission) => authStore.can(permission);
app.config.globalProperties.$hasRole = (role) => authStore.hasRole(role);

useThemeStore(pinia).initialize();

app.mount('#app');
