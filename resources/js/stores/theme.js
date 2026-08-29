import { defineStore } from 'pinia';
import api from '../utils/api';

const STORAGE_KEY = 'suntrack_theme';
const THEMES = ['light', 'dark'];

const storedTheme = () => {
    if (typeof window === 'undefined') return 'light';

    const value = window.localStorage.getItem(STORAGE_KEY);
    return THEMES.includes(value) ? value : 'light';
};

export const useThemeStore = defineStore('theme', {
    state: () => ({
        theme: storedTheme(),
        saving: false,
        loadedFromServer: false,
    }),

    getters: {
        isDark: (state) => state.theme === 'dark',
    },

    actions: {
        apply(theme) {
            const nextTheme = THEMES.includes(theme) ? theme : 'light';
            this.theme = nextTheme;

            document.documentElement.dataset.theme = nextTheme;
            document.documentElement.style.colorScheme = nextTheme;
            window.localStorage.setItem(STORAGE_KEY, nextTheme);

            const themeColor = document.querySelector('meta[name="theme-color"]');
            themeColor?.setAttribute('content', nextTheme === 'dark' ? '#0b1120' : '#f8fafc');
        },

        initialize() {
            this.apply(this.theme);
        },

        async loadPreference() {
            if (this.loadedFromServer) return;

            try {
                const response = await api.get('/admin/me/preferences');
                const preference = response.data?.data?.preferences?.theme;

                if (THEMES.includes(preference)) {
                    this.apply(preference);
                }

                this.loadedFromServer = true;
            } catch {
                // Local preference remains active when server preference is unavailable.
            }
        },

        async setTheme(theme) {
            const previousTheme = this.theme;
            this.apply(theme);
            this.saving = true;

            try {
                await api.put('/admin/me/preferences', { theme: this.theme });
                this.loadedFromServer = true;
            } catch {
                // Theme remains usable locally while offline or before authentication.
                if (!THEMES.includes(this.theme)) {
                    this.apply(previousTheme);
                }
            } finally {
                this.saving = false;
            }
        },

        toggle() {
            return this.setTheme(this.isDark ? 'light' : 'dark');
        },
    },
});
