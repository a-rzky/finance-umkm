<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { installPrompt, promptInstall } from '../pwa';

const page = usePage();
const user = computed(() => page.props.auth.user);
const currentPath = computed(() => new URL(page.url, 'http://localhost').pathname);

const toast = ref(null);
let toastTimer = null;

watch(
    () => page.props.flash,
    (flash) => {
        const message = flash?.success ?? flash?.error;

        if (!message) {
            return;
        }

        toast.value = { message, type: flash.success ? 'success' : 'error' };

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => (toast.value = null), 2500);
    },
    { deep: true }
);

const menu = [
    { href: '/', label: 'Kasir', icon: '🧮' },
    { href: '/riwayat', label: 'Riwayat', icon: '📋' },
    { href: '/rekap', label: 'Rekap', icon: '📊' },
];
</script>

<template>
    <div class="flex min-h-full flex-col bg-slate-100">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-white px-4 py-3">
            <div class="mx-auto flex max-w-lg items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ user.business_name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ user.username }}</p>
                </div>
                <button
                    class="shrink-0 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition active:bg-slate-100"
                    @click="router.post('/keluar')"
                >
                    Keluar
                </button>
            </div>
        </header>

        <main class="mx-auto w-full max-w-lg flex-1 pb-24">
            <div v-if="installPrompt" class="px-3 pt-3">
                <div class="flex items-center gap-3 rounded-2xl bg-teal-50 p-3">
                    <p class="flex-1 text-xs text-teal-900">
                        Pasang di layar utama supaya bisa dibuka seperti aplikasi biasa.
                    </p>
                    <button
                        class="shrink-0 rounded-xl bg-teal-700 px-3 py-2 text-xs font-semibold text-white"
                        @click="promptInstall"
                    >
                        Pasang
                    </button>
                </div>
            </div>

            <slot />
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)]">
            <div class="mx-auto flex max-w-lg">
                <Link
                    v-for="item in menu"
                    :key="item.href"
                    :href="item.href"
                    class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-xs font-medium transition"
                    :class="currentPath === item.href ? 'text-teal-700' : 'text-slate-400'"
                >
                    <span class="text-lg leading-none">{{ item.icon }}</span>
                    {{ item.label }}
                </Link>
            </div>
        </nav>

        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="-translate-y-3 opacity-0"
            leave-active-class="transition duration-200"
            leave-to-class="-translate-y-3 opacity-0"
        >
            <div v-if="toast" class="fixed inset-x-0 top-3 z-50 flex justify-center px-4">
                <div
                    class="rounded-xl px-4 py-2.5 text-sm font-medium text-white shadow-lg"
                    :class="toast.type === 'success' ? 'bg-teal-700' : 'bg-red-600'"
                >
                    {{ toast.message }}
                </div>
            </div>
        </Transition>
    </div>
</template>
