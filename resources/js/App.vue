<template>
  <router-view></router-view>
</template>

<script setup>
import { watch } from 'vue';
import { useAuthStore } from './stores/auth';
import { useThemeStore } from './stores/theme';

const authStore = useAuthStore();
const themeStore = useThemeStore();

watch(
  () => authStore.isAuthenticated,
  (isAuthenticated) => {
    if (isAuthenticated) themeStore.loadPreference();
  },
  { immediate: true },
);
</script>
