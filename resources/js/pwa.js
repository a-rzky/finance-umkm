import { ref } from 'vue';

/** Diisi browser saat aplikasi memenuhi syarat untuk dipasang. */
export const installPrompt = ref(null);

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
