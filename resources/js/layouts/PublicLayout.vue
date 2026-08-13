```vue
<template>
  <div class="min-h-screen flex flex-col bg-slate-50 font-sans text-slate-800">

    <!-- Header -->
    <header
      class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur-xl"
    >
      <div
        class="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
      >

        <!-- Brand -->
        <div class="flex items-center gap-3">
          <!-- Logo -->
          <div
            class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200"
          >
            <img
              src="/favicon.png"
              alt="SunTrack"
              class="h-9 w-9 object-contain"
            />
          </div>

          <!-- Brand Name -->
          <div class="flex items-center gap-2.5">
            <span
              class="text-xl font-bold tracking-tight"
              style="color: #ba5a5a"
            >
              SunTrack
            </span>

            <span
              class="hidden rounded-full px-3 py-1 text-[11px] font-semibold tracking-wide sm:inline-flex"
              style="
                background: #f7e49b;
                color: #785f18;
              "
            >
              Brand Portal
            </span>
          </div>
        </div>

        <!-- Security Status -->
        <div class="flex items-center gap-2.5 text-sm">
          <span
            class="hidden text-slate-500 md:inline"
          >
            Secure Review & Approval Workspace
          </span>

          <div
            class="flex h-8 w-8 items-center justify-center rounded-full"
            style="background: #eef7e9"
          >
            <span
              class="h-2.5 w-2.5 animate-pulse rounded-full"
              style="background: #a4ce8b"
            ></span>
          </div>

          <span
            class="hidden text-xs font-semibold sm:inline"
            style="color: #71985c"
          >
            Secure
          </span>
        </div>

      </div>

      <!-- Header Accent -->
      <div
        class="h-0.5 w-full"
        style="
          background: linear-gradient(
            90deg,
            #ba5a5a 0%,
            #f7e49b 35%,
            #a4ce8b 65%,
            #86bcbd 100%
          );
        "
      ></div>
    </header>

    <!-- Main Content -->
    <main
      class="flex-grow w-full mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10"
    >
      <slot></slot>
    </main>

    <!-- Footer -->
    <footer
      class="mt-12 border-t border-slate-200 bg-white"
    >
      <div
        class="mx-auto max-w-7xl px-4 py-7 text-center sm:px-6 lg:px-8"
      >

        <!-- Footer Brand -->
        <div class="mb-4 flex items-center justify-center gap-2">
          <div
            class="flex h-7 w-7 items-center justify-center rounded-lg"
            style="background: #f7e49b"
          >
            <span
              class="text-xs font-bold"
              style="color: #ba5a5a"
            >
              S
            </span>
          </div>

          <span
            class="text-sm font-bold"
            style="color: #ba5a5a"
          >
            SunTrack
          </span>
        </div>

        <p class="text-xs leading-5 text-slate-500">
          © {{ new Date().getFullYear() }} SunTrack Enterprise.
          Platform Kolaborasi Aman antara Brand & Admin.
        </p>

        <div class="mt-3 flex items-center justify-center gap-2">
          <span
            class="h-1.5 w-1.5 rounded-full"
            style="background: #a4ce8b"
          ></span>

          <p class="text-[11px] leading-5 text-slate-400">
            Seluruh aktivitas dalam sesi ini dicatat dan diamankan
            untuk kebutuhan audit sistem.
          </p>

          <span
            class="h-1.5 w-1.5 rounded-full"
            style="background: #86bcbd"
          ></span>
        </div>

        <!-- Security Indicator -->
        <div
          class="mx-auto mt-5 flex w-fit items-center gap-2 rounded-full px-3 py-1.5"
          style="background: #f4fafa"
        >
          <svg
            class="h-3.5 w-3.5"
            style="color: #86bcbd"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="m9 12 2 2 4-4"
            />
          </svg>

          <span
            class="text-[10px] font-semibold"
            style="color: #628f91"
          >
            Protected Review Session
          </span>
        </div>

      </div>
    </footer>

  </div>
</template>

<script setup>
// Public Layout for unauthenticated Brand Review pages
</script>