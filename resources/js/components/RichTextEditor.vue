<template>
  <div class="suntrack-quill overflow-hidden rounded-xl border border-default bg-surface focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100">
    <div ref="editor"></div>
  </div>
</template>

<script setup>
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Tulis catatan...' },
  label: { type: String, default: 'Editor teks' },
});
const emit = defineEmits(['update:modelValue']);
const editor = ref(null);
let quill = null;

const toolbar = [
  ['bold', 'italic', 'underline'],
  [{ list: 'bullet' }, { list: 'ordered' }],
  [{ header: [3, false] }],
  ['clean'],
];

const normalizedHtml = value => String(value || '').trim();
const editorHtml = () => {
  if (!quill || quill.getText().trim() === '') return '';
  return quill.getSemanticHTML().trim();
};
const emitValue = () => emit('update:modelValue', editorHtml());
const syncValue = async value => {
  await nextTick();
  if (!quill || document.activeElement === quill.root) return;
  if (normalizedHtml(editorHtml()) === normalizedHtml(value)) return;
  quill.clipboard.dangerouslyPasteHTML(value || '', 'silent');
};

onMounted(() => {
  quill = new Quill(editor.value, {
    theme: 'snow',
    placeholder: props.placeholder,
    modules: { toolbar },
    formats: ['bold', 'italic', 'underline', 'list', 'header'],
  });

  quill.root.setAttribute('aria-label', props.label);
  quill.root.setAttribute('inputmode', 'text');
  quill.root.setAttribute('enterkeyhint', 'enter');
  quill.root.setAttribute('autocapitalize', 'sentences');
  quill.root.setAttribute('spellcheck', 'true');
  quill.on('text-change', emitValue);
  syncValue(props.modelValue);
});

watch(() => props.modelValue, syncValue);

onBeforeUnmount(() => {
  quill?.off('text-change', emitValue);
  quill = null;
});
</script>

<style scoped>
.suntrack-quill :deep(.ql-toolbar.ql-snow) {
  display: flex;
  flex-wrap: wrap;
  gap: 0.125rem;
  border: 0;
  border-bottom: 1px solid var(--ui-border);
  background: var(--ui-surface-muted);
  padding: 0.25rem 0.375rem;
}

.suntrack-quill :deep(.ql-toolbar.ql-snow .ql-formats) {
  display: inline-flex;
  gap: 0.0625rem;
  margin: 0;
}

.suntrack-quill :deep(.ql-toolbar.ql-snow button),
.suntrack-quill :deep(.ql-toolbar.ql-snow .ql-picker-label) {
  width: 1.75rem;
  min-width: 1.75rem;
  height: 1.75rem;
  min-height: 1.75rem;
  padding: 0.3125rem;
}

.suntrack-quill :deep(.ql-toolbar.ql-snow button svg) {
  width: 1rem;
  height: 1rem;
}

.suntrack-quill :deep(.ql-toolbar.ql-snow .ql-picker.ql-header) {
  width: 5.25rem;
  font-size: 0.75rem;
}

.suntrack-quill :deep(.ql-toolbar.ql-snow .ql-picker.ql-header .ql-picker-label) {
  width: 100%;
  padding-left: 0.375rem;
  padding-right: 1.125rem;
}

.suntrack-quill :deep(.ql-container.ql-snow) {
  border: 0;
  color: var(--ui-content);
  font-family: inherit;
}

.suntrack-quill :deep(.ql-editor) {
  min-height: 8rem;
  padding: 0.75rem 1rem;
  color: var(--ui-content);
  font-size: 1rem;
  line-height: 1.75;
  overflow-wrap: anywhere;
}

.suntrack-quill :deep(.ql-editor.ql-blank::before) {
  left: 1rem;
  right: 1rem;
  color: var(--ui-content-muted);
  font-style: normal;
}

.suntrack-quill :deep(.ql-snow .ql-stroke) {
  stroke: var(--ui-content-soft);
}

.suntrack-quill :deep(.ql-snow .ql-fill) {
  fill: var(--ui-content-soft);
}

.suntrack-quill :deep(.ql-snow .ql-picker) {
  color: var(--ui-content-soft);
}
</style>
