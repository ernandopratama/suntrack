<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
    <!-- Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-600 flex items-center justify-center text-white font-bold text-xl shadow-md shadow-amber-500/20">
            ☀️
          </div>
          <div>
            <span class="text-xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">SunTrack</span>
            <span class="ml-2 px-2.5 py-0.5 text-xs font-semibold bg-amber-50 text-amber-700 rounded-full border border-amber-200/60">Brand Portal</span>
          </div>
        </div>
        <div class="flex items-center space-x-2 text-sm text-slate-500">
          <span class="hidden sm:inline">Secure Review & Approval Workspace</span>
          <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <slot></slot>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 mt-12 text-center text-xs text-slate-400">
      <div class="max-w-7xl mx-auto px-4">
        <p>© {{ new Date().getFullYear() }} SunTrack Enterprise. Platform Kolaborasi Aman antara Brand & Admin.</p>
        <p class="mt-1 text-slate-300">Seluruh aktivitas dalam sesi ini dicatat dan diamankan untuk kebutuhan audit sistem.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
// Public Layout for unauthenticated Brand Review pages
</script>
