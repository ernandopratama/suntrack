<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[9999] overflow-y-auto"
      >
        <!-- Backdrop Overlay -->
        <div
          class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity"
          @click="close"
        />

        <!-- Centering Wrapper -->
        <div
          class="flex min-h-full items-center justify-center p-4 text-center sm:p-6"
        >
          <Transition
            enter-active-class="duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
            enter-from-class="opacity-0 scale-95 translate-y-3"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-2"
          >
            <div
              v-if="isOpen"
              class="relative w-full max-w-xl transform overflow-hidden rounded-3xl bg-white text-left shadow-[0_25px_50px_-12px_rgba(0,0,0,0.15)] ring-1 ring-slate-900/5 transition-all p-6 sm:p-7"
            >
              <!-- Header -->
              <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h2 class="text-xl font-bold tracking-tight text-slate-800">
                  {{ title }}
                </h2>

                <button
                  type="button"
                  class="group rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600 active:scale-95 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-400/20"
                  @click="close"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 transition-transform group-hover:rotate-90 duration-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div class="max-h-[65vh] overflow-y-auto py-4 pr-1 text-slate-600">
                <slot />
              </div>

              <!-- Footer -->
              <div class="-mx-6 -mb-6 sm:-mx-7 sm:-mb-7 mt-6 flex justify-end gap-3 rounded-b-3xl bg-slate-50/80 px-6 py-4 border-t border-slate-100 sm:px-7">
                <slot name="footer">
                  <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50 hover:border-slate-300 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-slate-400/20"
                    @click="close"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    form="campaign-form"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/20 transition-all duration-200 hover:bg-blue-500 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                  >
                    Save
                  </button>
                </slot>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Modal'
  }
})

const emit = defineEmits([
  'close'
])

const close = () => {
  emit('close')
}
</script>