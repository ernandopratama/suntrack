<template>
  <div class="flex min-h-[65vh] items-center justify-center">
    <div class="w-full max-w-lg rounded-3xl border border-default bg-surface p-8 text-center shadow-sm">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-soft text-brand-strong">
        <i class="fa-solid fa-shield-halved text-2xl"></i>
      </div>
      <h1 class="mt-5 text-2xl font-bold text-content">Akses tidak tersedia</h1>
      <p class="mt-2 text-sm leading-6 text-content-soft">
        Akun Anda tidak memiliki permission untuk membuka halaman ini.
      </p>
      <router-link
        to="/dashboard"
        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
      >
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Kembali ke Dashboard
      </router-link>
    </div>
  </div>
</template>
