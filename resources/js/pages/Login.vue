```vue
<template>
  <div
    class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 sm:px-6 lg:px-8"
    style="background: #f5f9fa"
  >
    <!-- Background Decorations -->
    <div
      class="absolute -left-32 -top-32 h-96 w-96 rounded-full opacity-50"
      style="background: #d0e7e6"
    ></div>

    <div
      class="absolute -bottom-40 -right-32 h-[30rem] w-[30rem] rounded-full opacity-40"
      style="background: #95ccdd"
    ></div>

    <div
      class="absolute left-1/2 top-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-3xl"
      style="background: #4274d9"
    ></div>

    <!-- Login Card -->
    <div class="relative w-full max-w-md">
      <div
        class="overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-900/10 ring-1 ring-slate-100"
      >
        <!-- Top Accent -->
        <div
          class="h-1.5 w-full"
          style="
            background: linear-gradient(
              90deg,
              #293681 0%,
              #4274d9 45%,
              #95ccdd 75%,
              #d0e7e6 100%
            );
          "
        ></div>

        <div class="p-7 sm:p-9">
        <!-- Brand -->
        <div class="text-center">
          <div
            class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-100"
          >
            <img
              src="/favicon.png"
              alt="SunTrack"
              class="h-full w-full rounded-xl object-contain"
            />
          </div>

          <h1
            class="mt-5 text-3xl font-bold tracking-tight"
            style="color: #293681"
          >
            SunTrack
          </h1>

          <p class="mt-1.5 text-sm font-medium text-slate-500">
            Admin Portal
          </p>

          <p class="mt-4 text-sm leading-6 text-slate-400">
            Sign in to manage your SunTrack workspace.
          </p>
        </div>

          <!-- Form -->
          <form class="mt-8 space-y-5" @submit.prevent="login">

            <!-- Error Message -->
            <div
              v-if="errorMsg"
              class="flex items-start gap-3 rounded-2xl border p-4"
              style="
                background: #fff5f5;
                border-color: #fecaca;
                color: #991b1b;
              "
            >
              <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
                style="background: #fee2e2"
              >
                <svg
                  class="h-4 w-4 text-red-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v3m0 4h.01M10.29 3.86 2.82 17a2 2 0 0 0 1.74 3h14.88a2 2 0 0 0 1.74-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                  />
                </svg>
              </div>

              <div>
                <p class="text-sm font-semibold">
                  Unable to sign in
                </p>

                <p class="mt-0.5 text-xs leading-5 text-red-700">
                  {{ errorMsg }}
                </p>
              </div>
            </div>

            <!-- Email -->
            <div>
              <label
                for="email-address"
                class="mb-2 block text-sm font-semibold text-slate-700"
              >
                Email address
              </label>

              <div class="relative">
                <div
                  class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
                >
                  <svg
                    class="h-5 w-5 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="1.7"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M3 8l9 6 9-6M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"
                    />
                  </svg>
                </div>

                <input
                  id="email-address"
                  name="email"
                  type="email"
                  v-model="form.email"
                  required
                  autocomplete="email"
                  class="login-input pl-11"
                  placeholder="Enter your email"
                />
              </div>
            </div>

            <!-- Password -->
            <div>
              <label
                for="password"
                class="mb-2 block text-sm font-semibold text-slate-700"
              >
                Password
              </label>

              <div class="relative">
                <div
                  class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
                >
                  <svg
                    class="h-5 w-5 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="1.7"
                  >
                    <rect
                      x="4"
                      y="10"
                      width="16"
                      height="11"
                      rx="2"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M8 10V7a4 4 0 0 1 8 0v3"
                    />
                  </svg>
                </div>

                <input
                  id="password"
                  name="password"
                  type="password"
                  v-model="form.password"
                  required
                  autocomplete="current-password"
                  class="login-input pl-11"
                  placeholder="Enter your password"
                />
              </div>
            </div>

            <!-- Submit -->
            <div class="pt-2">
              <button
                type="submit"
                :disabled="loading"
                class="group flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
                style="
                  background: #293681;
                  box-shadow: 0 10px 24px rgba(41, 54, 129, 0.2);
                "
              >
                <span
                  v-if="loading"
                  class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                ></span>

                <svg
                  v-else
                  class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-0.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 19l-7-7 7-7"
                    transform="rotate(180 12 12)"
                  />
                </svg>

                <span>
                  {{ loading ? 'Signing in...' : 'Sign in to Dashboard' }}
                </span>
              </button>
            </div>
          </form>

          <!-- Footer -->
          <div class="mt-8 flex items-center justify-center gap-2">
            <div
              class="h-1.5 w-1.5 rounded-full"
              style="background: #293681"
            ></div>

            <div
              class="h-1.5 w-1.5 rounded-full"
              style="background: #4274d9"
            ></div>

            <div
              class="h-1.5 w-1.5 rounded-full"
              style="background: #95ccdd"
            ></div>

            <p class="ml-1 text-xs text-slate-400">
              SunTrack Admin Portal
            </p>
          </div>
        </div>
      </div>

      <!-- Bottom Text -->
      <p class="mt-5 text-center text-xs text-slate-400">
        Secure access to your administrative workspace
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  email: 'admin@suntrack.com',
  password: 'password',
});

const loading = ref(false);
const errorMsg = ref('');

const login = async () => {
  loading.value = true;
  errorMsg.value = '';

  try {
    const success = await authStore.login(form.value);

    if (success) {
      router.push('/dashboard');
    } else {
      errorMsg.value =
        'Login failed. Please check your credentials.';
    }
  } catch (e) {
    errorMsg.value =
      e.response?.data?.message ||
      'An error occurred during login.';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.login-input {
  display: block;
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  padding-top: 0.75rem;
  padding-right: 0.875rem;
  padding-bottom: 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #1e293b;
  outline: none;
  transition:
    border-color 150ms ease,
    box-shadow 150ms ease,
    background-color 150ms ease;
}

.login-input::placeholder {
  color: #94a3b8;
}

.login-input:hover {
  border-color: #95ccdd;
}

.login-input:focus {
  border-color: #4274d9;
  box-shadow: 0 0 0 3px rgba(66, 116, 217, 0.12);
}

.login-input:focus::placeholder {
  color: #cbd5e1;
}
</style>