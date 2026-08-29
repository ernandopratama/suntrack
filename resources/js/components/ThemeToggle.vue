<template>
  <button
    type="button"
    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-default bg-surface text-content-soft shadow-sm transition hover:bg-surface-muted hover:text-brand focus:outline-none focus:ring-4 focus:ring-brand/15 disabled:cursor-wait disabled:opacity-60"
    :aria-label="label"
    :title="label"
    :disabled="themeStore.saving"
    @click="themeStore.toggle"
  >
    <i
      class="text-sm"
      :class="themeStore.isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"
      aria-hidden="true"
    ></i>
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { useThemeStore } from '../stores/theme';

const themeStore = useThemeStore();
const label = computed(() => themeStore.isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
</script>
