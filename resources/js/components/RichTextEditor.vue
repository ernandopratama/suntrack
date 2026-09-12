<template>
  <div class="overflow-hidden rounded-xl border border-default bg-surface focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100">
    <div class="flex flex-wrap gap-1 border-b border-default bg-surface-muted p-2">
      <button v-for="action in actions" :key="action.command" type="button" :title="action.label" class="flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-bold text-content-soft hover:bg-surface hover:text-blue-600" @mousedown.prevent="apply(action)">
        <i v-if="action.icon" :class="action.icon"></i>
        <span v-else>{{ action.short }}</span>
      </button>
    </div>
    <div
      ref="editor"
      class="suntrack-rich-editor min-h-32 px-4 py-3 text-sm leading-7 text-content outline-none"
      contenteditable="true"
      role="textbox"
      aria-multiline="true"
      :data-placeholder="placeholder"
      @input="emitValue"
      @blur="emitValue"
    ></div>
  </div>
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Tulis catatan...' },
});
const emit = defineEmits(['update:modelValue']);
const editor = ref(null);
const actions = [
  { command: 'bold', label: 'Tebal', icon: 'fa-solid fa-bold' },
  { command: 'italic', label: 'Miring', icon: 'fa-solid fa-italic' },
  { command: 'underline', label: 'Garis bawah', icon: 'fa-solid fa-underline' },
  { command: 'insertUnorderedList', label: 'Daftar poin', icon: 'fa-solid fa-list-ul' },
  { command: 'insertOrderedList', label: 'Daftar angka', icon: 'fa-solid fa-list-ol' },
  { command: 'formatBlock', value: 'h3', label: 'Subjudul', short: 'H3' },
  { command: 'formatBlock', value: 'p', label: 'Paragraf', short: 'P' },
];

const sync = async () => {
  await nextTick();
  if (editor.value && editor.value.innerHTML !== (props.modelValue || '') && document.activeElement !== editor.value) {
    editor.value.innerHTML = props.modelValue || '';
  }
};
const emitValue = () => emit('update:modelValue', editor.value?.innerHTML || '');
const apply = (action) => {
  editor.value?.focus();
  document.execCommand(action.command, false, action.value || null);
  emitValue();
};

onMounted(sync);
watch(() => props.modelValue, sync);
</script>

<style scoped>
.suntrack-rich-editor:empty::before {
  color: #94a3b8;
  content: attr(data-placeholder);
  pointer-events: none;
}

.suntrack-rich-editor :deep(ul) { list-style: disc; padding-left: 1.25rem; }
.suntrack-rich-editor :deep(ol) { list-style: decimal; padding-left: 1.25rem; }
.suntrack-rich-editor :deep(h3) { font-size: 1rem; font-weight: 700; }
.suntrack-rich-editor :deep(blockquote) { border-left: 3px solid #94a3b8; padding-left: .75rem; }
</style>
