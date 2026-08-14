import { computed, ref } from 'vue';

/** Diisi browser saat aplikasi memenuhi syarat untuk dipasang. */
const installPrompt = ref(null);

const DISMISS_KEY = 'pwa-install-dismissed';

/**
 * localStorage bisa dilarang browser (mode penyamaran, setelan privasi).
 * Kegagalan membaca atau menulis tidak boleh menjatuhkan aplikasi — paling
 * buruk tawaran pasang muncul lagi nanti.
 */
const readDismissed = () => {
    try {
        return localStorage.getItem(DISMISS_KEY) === '1';
    } catch {
        return false;
    }
};

const dismissed = ref(readDismissed());

/**
 * Tawaran pasang hanya layak tampil bila browser memang menawarkannya
 * (artinya aplikasi belum terpasang) dan pengguna belum menutupnya.
 */
export const canInstall = computed(() => installPrompt.value !== null && !dismissed.value);

export const dismissInstall = () => {
    dismissed.value = true;

    try {
        localStorage.setItem(DISMISS_KEY, '1');
    } catch {
        // Diabaikan: status tutup cukup berlaku selama sesi ini.
    }
};

export const registerServiceWorker = () => {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error) => {
            // Kegagalan pendaftaran tidak boleh menghentikan aplikasi —
            // tanpa service worker aplikasi tetap berfungsi penuh saat online.
            console.warn('Service worker gagal didaftarkan:', error);
        });
    });
};

export const listenForInstallPrompt = () => {
    window.addEventListener('beforeinstallprompt', (event) => {
        // Ditahan agar tombol pasang bisa muncul di tempat yang kita tentukan.
        event.preventDefault();
        installPrompt.value = event;
    });

    window.addEventListener('appinstalled', () => {
        installPrompt.value = null;
    });
};

export const promptInstall = async () => {
    if (!installPrompt.value) {
        return;
    }

    installPrompt.value.prompt();
    await installPrompt.value.userChoice;

    // Prompt hanya sah sekali pakai.
    installPrompt.value = null;
};
