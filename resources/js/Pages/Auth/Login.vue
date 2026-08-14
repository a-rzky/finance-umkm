<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    username: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post('/masuk', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk" />

    <main class="flex min-h-full flex-col justify-center px-5 py-10">
        <div class="mx-auto w-full max-w-sm">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-teal-700 text-2xl">
                    🧾
                </div>
                <h1 class="text-xl font-semibold text-slate-900">Catatan Usaha</h1>
                <p class="mt-1 text-sm text-slate-500">Masuk ke toko kamu</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label for="username" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Nama pengguna
                    </label>
                    <input
                        id="username"
                        v-model="form.username"
                        type="text"
                        autocomplete="username"
                        autocapitalize="none"
                        autofocus
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                    />
                    <p v-if="form.errors.username" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.username }}
                    </p>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Kata sandi
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20"
                    />
                    <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="size-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600/30"
                    />
                    Biarkan saya tetap masuk
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-teal-700 px-4 py-3.5 text-base font-semibold text-white transition active:bg-teal-800 disabled:opacity-60"
                >
                    {{ form.processing ? 'Memproses…' : 'Masuk' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Belum punya toko?
                <Link href="/daftar" class="font-semibold text-teal-700">Buat toko</Link>
            </p>
        </div>
    </main>
</template>
