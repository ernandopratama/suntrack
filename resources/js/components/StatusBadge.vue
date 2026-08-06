<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', colorClass]">
    {{ status }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, required: true },
});

const colorClass = computed(() => {
  const s = props.status.toLowerCase();
  if (s.includes('approved') || s === 'completed' || s === 'running') return 'bg-green-100 text-green-800';
  if (s.includes('rejected') || s === 'cancelled') return 'bg-red-100 text-red-800';
  if (s.includes('revision') || s.includes('partially')) return 'bg-orange-100 text-orange-800';
  if (s.includes('progress')) return 'bg-blue-100 text-blue-800';
  return 'bg-gray-100 text-gray-800'; // Default for Pending, Draft, Not Started
});
</script>
