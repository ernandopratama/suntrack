<template>
  <div class="text-center py-12 px-4 sm:px-6 lg:px-8 border-2 border-dashed border-gray-300 rounded-lg">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath" />
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ title }}</h3>
    <p class="mt-1 text-sm text-gray-500">{{ description }}</p>
    <div v-if="$slots.action" class="mt-6">
      <slot name="action"></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: 'No items' },
  description: { type: String, default: 'Get started by creating a new item.' },
  iconPath: { type: String, default: 'M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z' }
});
</script>
