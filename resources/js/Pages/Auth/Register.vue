<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    business_name: '',
    username: '',
    password: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post('/daftar', {
        onFinish: () => form.reset('password'),
    });
};

const inputClass =
    'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20';
</script>

<template>
    <Head title="Daftar toko" />

    <main class="flex min-h-full flex-col justify-center px-5 py-10">
        <div class="mx-auto w-full max-w-sm">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-teal-700 text-2xl">
                    🧾
                </div>
                <h1 class="text-xl font-semibold text-slate-900">Buat toko kamu</h1>
                <p class="mt-1 text-sm text-slate-500">Tiga isian, langsung bisa dipakai</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label for="business_name" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Nama toko
                    </label>
                    <input
                        id="business_name"
                        v-model="form.business_name"
                        type="text"
                        placeholder="Warung Bu Sri"
                        autofocus
                        :class="inputClass"
                    />
                    <p v-if="form.errors.business_name" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.business_name }}
                    </p>
                </div>

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
                        placeholder="warungbusri"
                        :class="inputClass"
                    />
                    <p class="mt-1.5 text-xs text-slate-500">Dipakai untuk masuk. Huruf kecil, angka, titik.</p>
                    <p v-if="form.errors.username" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.username }}
                    </p>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Kata sandi
                    </label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            :class="[inputClass, 'pr-16']"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-teal-700"
                            @click="showPassword = !showPassword"
                        >
                            {{ showPassword ? 'Tutup' : 'Lihat' }}
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">
                        Minimal 8 karakter. Catat baik-baik — belum ada fitur lupa sandi.
                    </p>
                    <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-teal-700 px-4 py-3.5 text-base font-semibold text-white transition active:bg-teal-800 disabled:opacity-60"
                >
                    {{ form.processing ? 'Membuat toko…' : 'Buat toko' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Sudah punya toko?
                <Link href="/masuk" class="font-semibold text-teal-700">Masuk</Link>
            </p>
        </div>
    </main>
</template>
