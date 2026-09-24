import { readonly, ref } from 'vue';

const deferredInstallPrompt = ref(null);
const installed = ref(false);
const ios = ref(false);
let initialized = false;

const detectInstalled = () => (
  window.matchMedia('(display-mode: standalone)').matches
  || window.navigator.standalone === true
);

export const initializePwa = () => {
  if (initialized || typeof window === 'undefined') return;

  initialized = true;
  installed.value = detectInstalled();
  ios.value = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

  window.addEventListener('beforeinstallprompt', (event) => {
    deferredInstallPrompt.value = event;
  });

  window.addEventListener('appinstalled', () => {
    installed.value = true;
    deferredInstallPrompt.value = null;
  });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').catch((error) => {
        console.error('Service worker registration failed:', error);
      });
    });
  }
};

export const usePwaInstall = () => {
  const requestInstall = async () => {
    if (installed.value) return 'installed';

    if (!deferredInstallPrompt.value) {
      return ios.value ? 'ios' : 'manual';
    }

    const prompt = deferredInstallPrompt.value;
    deferredInstallPrompt.value = null;
    await prompt.prompt();

    const choice = await prompt.userChoice;
    return choice.outcome;
  };

  return {
    isInstalled: readonly(installed),
    isIos: readonly(ios),
    requestInstall,
  };
};
