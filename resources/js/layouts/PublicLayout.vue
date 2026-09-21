<template>
  <div class="public-layout min-h-screen flex flex-col font-sans text-[#292D2F]">

    <!-- =========================================================
         HEADER
    ========================================================== -->
    <header
      class="public-header sticky top-0 z-40"
    >
      <div
        class="mx-auto flex h-[72px] max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
      >

        <!-- =====================================================
             BRAND
        ====================================================== -->
        <div class="flex min-w-0 items-center gap-3">

          <!-- Logo -->
          <div class="brand-logo">
            <img
              src="/favicon.png"
              alt="SunTrack"
              class="h-9 w-9 object-contain"
            />
          </div>

          <!-- Brand Identity -->
          <div class="min-w-0">
            <div class="flex items-center gap-2.5">

              <span
                class="text-xl font-extrabold tracking-[-0.03em] text-[#D73535]"
              >
                SunTrack
              </span>

              <span
                class="hidden items-center rounded-full border border-[#FFD41D]/70 bg-[#FFD41D]/18 px-3 py-1 text-[10px] font-extrabold uppercase tracking-[0.12em] text-[#755700] sm:inline-flex"
              >
                Brand Portal
              </span>

            </div>

            <p
              class="mt-0.5 hidden text-[10px] font-medium tracking-wide text-[#909795] md:block"
            >
              Enterprise Review & Collaboration
            </p>
          </div>

        </div>

        <!-- =====================================================
             SECURITY STATUS
        ====================================================== -->
        <div class="flex shrink-0 items-center gap-3">

          <ThemeToggle
            local-only
            show-label
            storage-key="suntrack_public_theme"
            class="public-theme-toggle"
          />

          <div class="hidden text-right lg:block">
            <p class="text-xs font-semibold text-[#555E5C]">
              Secure Review Workspace
            </p>

            <p class="mt-0.5 text-[10px] text-[#959C9A]">
              Protected public session
            </p>
          </div>

          <div class="security-badge">
            <span class="security-indicator">
              <span class="security-dot"></span>
            </span>

            <span class="hidden text-xs font-bold text-[#A84900] sm:block">
              Secure
            </span>
          </div>

        </div>

      </div>

      <!-- Brand Accent -->
      <div class="brand-accent"></div>
    </header>

    <!-- =========================================================
         MAIN
    ========================================================== -->
    <main
      class="relative z-10 flex-grow w-full mx-auto max-w-7xl px-4 py-7 sm:px-6 sm:py-8 lg:px-8 lg:py-10"
    >
      <slot />
    </main>

    <!-- =========================================================
         FOOTER
    ========================================================== -->
    <footer
      class="public-footer relative z-10 mt-10 border-t border-[#EAECEA]/90 bg-white/80 backdrop-blur-xl"
    >
      <div
        class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
      >

        <div
          class="flex flex-col items-center justify-between gap-6 md:flex-row"
        >

          <!-- Footer Brand -->
          <div
            class="flex flex-col items-center text-center md:items-start md:text-left"
          >
            <div class="flex items-center gap-2.5">

              <div class="footer-logo">
                <img
                  src="/favicon.png"
                  alt="SunTrack"
                  class="h-5 w-5 object-contain"
                />
              </div>

              <span
                class="text-sm font-extrabold tracking-tight text-[#D73535]"
              >
                SunTrack Enterprise
              </span>

            </div>

            <p
              class="mt-2 max-w-lg text-[11px] leading-5 text-[#7D8583]"
            >
              Platform kolaborasi, monitoring, review, dan approval
              antara Brand, Client, PIC, dan Tim SUNTRACK.
            </p>
          </div>

          <!-- Secure Session -->
          <div class="footer-security">

            <div class="footer-security-icon">
              <svg
                class="h-4 w-4"
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
            </div>

            <div>
              <p
                class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-[#A84900]"
              >
                Protected Session
              </p>

              <p
                class="mt-0.5 text-[10px] text-[#969D9B]"
              >
                Aktivitas dapat dicatat untuk kebutuhan audit
              </p>
            </div>

          </div>

        </div>

        <!-- Footer Bottom -->
        <div
          class="mt-7 flex flex-col items-center justify-between gap-3 border-t border-[#ECEEED] pt-5 text-center sm:flex-row sm:text-left"
        >
          <p class="text-[10px] text-[#9AA19F]">
            © {{ currentYear }} SunTrack Enterprise.
            All rights reserved.
          </p>

          <div
            class="flex items-center gap-2 text-[10px] font-semibold text-[#8A9290]"
          >
            <span class="footer-dot footer-dot--yellow"></span>

            <span>
              Secure Review
            </span>

            <span class="text-[#D4D8D6]">
              •
            </span>

            <span>
              Approval
            </span>

            <span class="text-[#D4D8D6]">
              •
            </span>

            <span>
              Collaboration
            </span>

            <span class="footer-dot footer-dot--red"></span>
          </div>
        </div>

      </div>
    </footer>

  </div>
</template>

<script setup>
import { computed } from 'vue';
import ThemeToggle from '../components/ThemeToggle.vue';
import { useThemeStore } from '../stores/theme';

const PUBLIC_THEME_KEY = 'suntrack_public_theme';
const themeStore = useThemeStore();
const storedPublicTheme = window.localStorage.getItem(PUBLIC_THEME_KEY);
const publicTheme = storedPublicTheme === 'dark' ? 'dark' : 'light';

themeStore.apply(publicTheme, false);

const currentYear = computed(() => new Date().getFullYear());
</script>

<style scoped>
/* ============================================================
   SUNTRACK PUBLIC LAYOUT
============================================================ */

.public-layout {
  --sun-yellow: #FFD41D;
  --sun-orange: #FFA240;
  --sun-red: #D73535;
  --sun-signal: #FF4646;

  position: relative;
  overflow-x: hidden;

  background:
    radial-gradient(
      circle at 7% 8%,
      rgba(255, 212, 29, 0.12),
      transparent 24rem
    ),
    radial-gradient(
      circle at 95% 12%,
      rgba(255, 70, 70, 0.07),
      transparent 28rem
    ),
    linear-gradient(
      180deg,
      #fffdf9 0%,
      #ffffff 35%,
      #fffaf8 100%
    );
}

/* ============================================================
   HEADER
============================================================ */

.public-header {
  border-bottom: 1px solid rgba(220, 224, 222, 0.85);

  background:
    rgba(255, 255, 255, 0.88);

  backdrop-filter:
    blur(18px);

  -webkit-backdrop-filter:
    blur(18px);

  box-shadow:
    0 1px 2px rgba(35, 40, 39, 0.02),
    0 8px 30px rgba(35, 40, 39, 0.035);
}

/* ============================================================
   LOGO
============================================================ */

.brand-logo {
  position: relative;

  display: flex;

  width: 44px;
  height: 44px;

  flex-shrink: 0;

  align-items: center;
  justify-content: center;

  overflow: hidden;

  border:
    1px solid rgba(215, 53, 53, 0.09);

  border-radius:
    15px;

  background:
    #ffffff;

  box-shadow:
    0 5px 16px rgba(42, 47, 46, 0.07);
}

.brand-logo::after {
  content: "";

  position: absolute;

  right: -5px;
  bottom: -5px;

  width: 16px;
  height: 16px;

  border-radius: 999px;

  background:
    var(--sun-yellow);
}

/* ============================================================
   HEADER SECURITY
============================================================ */

.security-badge {
  display: flex;

  align-items: center;

  gap: 8px;

  padding:
    7px 10px;

  border:
    1px solid rgba(255, 162, 64, 0.23);

  border-radius:
    999px;

  background:
    rgba(255, 162, 64, 0.08);
}

.security-indicator {
  display: flex;

  width: 22px;
  height: 22px;

  align-items: center;
  justify-content: center;

  border-radius:
    999px;

  background:
    rgba(255, 212, 29, 0.22);
}

.security-dot {
  width: 8px;
  height: 8px;

  border-radius:
    999px;

  background:
    var(--sun-yellow);

  box-shadow:
    0 0 0 4px rgba(255, 212, 29, 0.16);

  animation:
    securePulse 2s ease-in-out infinite;
}

@keyframes securePulse {
  0%,
  100% {
    box-shadow:
      0 0 0 3px rgba(255, 212, 29, 0.12);
  }

  50% {
    box-shadow:
      0 0 0 7px rgba(255, 212, 29, 0.04);
  }
}

/* ============================================================
   BRAND ACCENT
============================================================ */

.brand-accent {
  height: 3px;

  width: 100%;

  background:
    linear-gradient(
      90deg,
      var(--sun-yellow) 0%,
      var(--sun-orange) 32%,
      var(--sun-signal) 66%,
      var(--sun-red) 100%
    );
}

/* ============================================================
   FOOTER BRAND
============================================================ */

.footer-logo {
  display: flex;

  width: 30px;
  height: 30px;

  align-items: center;
  justify-content: center;

  border:
    1px solid rgba(255, 212, 29, 0.36);

  border-radius:
    10px;

  background:
    rgba(255, 212, 29, 0.12);
}

/* ============================================================
   FOOTER SECURITY
============================================================ */

.footer-security {
  display: flex;

  align-items: center;

  gap: 10px;

  padding:
    10px 14px;

  border:
    1px solid rgba(255, 162, 64, 0.20);

  border-radius:
    14px;

  background:
    linear-gradient(
      135deg,
      rgba(255, 212, 29, 0.08),
      rgba(255, 162, 64, 0.07)
    );
}

.footer-security-icon {
  display: flex;

  width: 32px;
  height: 32px;

  flex-shrink: 0;

  align-items: center;
  justify-content: center;

  border-radius:
    10px;

  color:
    #d73535;

  background:
    rgba(255, 70, 70, 0.08);
}

/* ============================================================
   FOOTER DOTS
============================================================ */

.footer-dot {
  display: inline-block;

  width: 6px;
  height: 6px;

  border-radius:
    999px;
}

.footer-dot--yellow {
  background:
    var(--sun-yellow);

  box-shadow:
    0 0 0 3px rgba(255, 212, 29, 0.10);
}

.footer-dot--red {
  background:
    var(--sun-signal);

  box-shadow:
    0 0 0 3px rgba(255, 70, 70, 0.08);
}

.public-theme-toggle {
  border-color: rgba(255, 162, 64, 0.24);
  background: rgba(255, 255, 255, 0.82);
  box-shadow: 0 5px 16px rgba(42, 47, 46, 0.06);
}

:global(:root[data-theme='dark']) .public-layout {
  color: #e7eeeb;
  background:
    radial-gradient(circle at 7% 8%, rgba(255, 212, 29, 0.07), transparent 24rem),
    radial-gradient(circle at 95% 12%, rgba(255, 70, 70, 0.08), transparent 28rem),
    linear-gradient(180deg, #0b1211 0%, #101917 48%, #121312 100%);
}

:global(:root[data-theme='dark']) .public-header {
  border-bottom-color: rgba(117, 132, 126, 0.2);
  background: rgba(13, 21, 19, 0.88);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

:global(:root[data-theme='dark']) .public-header p {
  color: #aebbb6 !important;
}

:global(:root[data-theme='dark']) .public-theme-toggle {
  border-color: rgba(255, 162, 64, 0.3);
  color: #ffe9ca;
  background: rgba(255, 255, 255, 0.07);
}

:global(:root[data-theme='dark']) .public-theme-toggle:hover {
  color: #ffd41d;
  background: rgba(255, 162, 64, 0.13);
}

:global(:root[data-theme='dark']) .security-badge,
:global(:root[data-theme='dark']) .footer-security {
  border-color: rgba(255, 162, 64, 0.25);
  background: rgba(255, 162, 64, 0.08);
}

:global(:root[data-theme='dark']) .security-badge > span:last-child {
  color: #ffbd78;
}

:global(:root[data-theme='dark']) .public-footer {
  border-top-color: rgba(117, 132, 126, 0.2);
  background: rgba(13, 21, 19, 0.88) !important;
}

:global(:root[data-theme='dark']) .public-footer p,
:global(:root[data-theme='dark']) .public-footer span {
  color: #aebbb6;
}

/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 640px) {
  .public-header > div:first-child {
    height:
      64px;
  }

  .brand-logo {
    width:
      40px;

    height:
      40px;

    border-radius:
      13px;
  }

  .brand-logo img {
    width:
      32px;

    height:
      32px;
  }

  .security-badge {
    padding:
      6px;
  }

  .security-indicator {
    width:
      24px;

    height:
      24px;
  }
}

/* ============================================================
   ACCESSIBILITY
============================================================ */

@media (prefers-reduced-motion: reduce) {
  .security-dot {
    animation:
      none;
  }
}
</style>
