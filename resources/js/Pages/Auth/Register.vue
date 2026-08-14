<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    business_name: '',
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/daftar', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const inputClass =
    'w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-base text-slate-900 outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20';
</script>

<template>
    <Head title="Daftar usaha" />

    <main class="flex min-h-full flex-col justify-center px-5 py-10">
        <div class="mx-auto w-full max-w-sm">
            <div class="mb-8 text-center">
                <h1 class="text-xl font-semibold text-slate-900">Daftar usaha</h1>
                <p class="mt-1 text-sm text-slate-500">Gratis, langsung bisa dipakai</p>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label for="business_name" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Nama usaha
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
                    <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Nama pemilik
                    </label>
                    <input id="name" v-model="form.name" type="text" placeholder="Sri Wahyuni" :class="inputClass" />
                    <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.name }}
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
                    <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Email <span class="font-normal text-slate-400">(opsional)</span>
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        autocapitalize="none"
                        :class="inputClass"
                    />
                    <p class="mt-1.5 text-xs text-slate-500">
                        Satu-satunya cara memulihkan akun kalau kata sandi lupa.
                    </p>
                    <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.email }}
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
                        autocomplete="new-password"
                        :class="inputClass"
                    />
                    <p v-if="form.errors.password" class="mt-1.5 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">
                        Ulangi kata sandi
                    </label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        :class="inputClass"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-teal-700 px-4 py-3.5 text-base font-semibold text-white transition active:bg-teal-800 disabled:opacity-60"
                >
                    {{ form.processing ? 'Memproses…' : 'Daftar' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Sudah punya akun?
                <Link href="/masuk" class="font-semibold text-teal-700">Masuk</Link>
            </p>
        </div>
    </main>
</template>
