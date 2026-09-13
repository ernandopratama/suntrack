<template>
  <button
    type="button"
    class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-default bg-surface text-content-soft shadow-sm transition hover:bg-surface-muted hover:text-brand focus:outline-none focus:ring-4 focus:ring-brand/15 disabled:cursor-wait disabled:opacity-60"
    :class="showLabel ? 'gap-2 px-3 sm:px-4' : 'w-10'"
    :aria-label="label"
    :title="label"
    :disabled="themeStore.saving"
    @click="toggleTheme"
  >
    <i
      class="text-sm"
      :class="themeStore.isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon'"
      aria-hidden="true"
    ></i>
    <span v-if="showLabel" class="hidden text-xs font-bold sm:inline">
      {{ themeStore.isDark ? 'Mode terang' : 'Mode gelap' }}
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { useThemeStore } from '../stores/theme';

const { localOnly, showLabel } = defineProps({
  localOnly: { type: Boolean, default: false },
  showLabel: { type: Boolean, default: false },
});

const themeStore = useThemeStore();
const label = computed(() => themeStore.isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
const toggleTheme = () => {
  if (localOnly) {
    themeStore.apply(themeStore.isDark ? 'light' : 'dark');
    return;
  }

  themeStore.toggle();
};
</script>
