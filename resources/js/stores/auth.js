import { defineStore } from 'pinia';
import api from '../utils/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAuthenticated: false,
    }),
    actions: {
        async login(credentials) {
            // Get CSRF cookie first for Sanctum SPA auth (must use same origin with credentials)
            await api.get('/sanctum/csrf-cookie', { baseURL: '/' });
            
            const response = await api.post('/auth/login', credentials);
            
            if (response.data.success) {
                this.user = response.data.data.user;
                this.isAuthenticated = true;
                return true;
            }
            return false;
        },
        
        async logout() {
            await api.post('/auth/logout');
            this.user = null;
            this.isAuthenticated = false;
        },

        async fetchUser() {
            try {
                const response = await api.get('/auth/user');
                if (response.data.success) {
                    this.user = response.data.data.user;
                    this.isAuthenticated = true;
                }
            } catch (error) {
                this.user = null;
                this.isAuthenticated = false;
            }
        }
    }
});
