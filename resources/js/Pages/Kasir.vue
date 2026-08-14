<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { formatNumber, formatRupiah } from '../rupiah';

const props = defineProps({
    categories: { type: Array, required: true },
    today: { type: Object, required: true },
    todayDate: { type: String, required: true },
    recent: { type: Array, required: true },
});

// Nominal disimpan sebagai string digit supaya penekanan numpad tidak
// terpengaruh pembulatan angka, baru dikirim sebagai integer saat simpan.
const digits = ref('');
const type = ref('masuk');
const categoryId = ref(null);
const note = ref('');

const amount = computed(() => Number(digits.value || '0'));
const visibleCategories = computed(() => props.categories.filter((c) => c.type === type.value));

const form = useForm({
    type: 'masuk',
    amount: 0,
    occurred_on: props.todayDate,
    note: '',
    category_id: null,
});

const press = (key) => {
    if (key === 'back') {
        digits.value = digits.value.slice(0, -1);

        return;
    }

    const next = digits.value + key;

    // 13 digit sudah jauh di atas batas nominal yang divalidasi server.
    if (next.replace(/^0+/, '').length > 13) {
        return;
    }

    digits.value = next.replace(/^0+(?=\d)/, '');
};

const selectType = (value) => {
    type.value = value;
    // Kategori terikat pada jenis transaksi, jadi pilihan lama tidak lagi sah.
    categoryId.value = null;
};

const reset = () => {
    digits.value = '';
    categoryId.value = null;
    note.value = '';
};

const submit = () => {
    if (amount.value < 1 || form.processing) {
        return;
    }

    form.transform(() => ({
        type: type.value,
        amount: amount.value,
        occurred_on: props.todayDate,
        note: note.value || null,
        category_id: categoryId.value,
    })).post('/transaksi', {
        preserveScroll: true,
        onSuccess: () => reset(),
    });
};

const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '000', '0', 'back'];
</script>

<template>
    <Head title="Kasir" />

    <AppLayout>
        <div class="space-y-3 p-3">
            <!-- Ringkasan hari ini -->
            <div class="grid grid-cols-3 gap-2 rounded-2xl bg-white p-3">
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Masuk</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-teal-700">{{ formatRupiah(today.masuk) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Keluar</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-red-600">{{ formatRupiah(today.keluar) }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-medium text-slate-500">Sisa</p>
                    <p
                        class="mt-0.5 truncate text-sm font-semibold"
                        :class="today.saldo < 0 ? 'text-red-600' : 'text-slate-900'"
                    >
                        {{ formatRupiah(today.saldo) }}
                    </p>
                </div>
            </div>

            <!-- Layar nominal -->
            <div class="rounded-2xl bg-slate-900 px-5 py-6 text-right">
                <p class="text-xs font-medium text-slate-400">Nominal</p>
                <p class="mt-1 truncate text-4xl font-bold tabular-nums text-white">
                    <span class="mr-1 text-xl font-semibold text-slate-400">Rp</span>{{ formatNumber(amount) }}
                </p>
            </div>

            <!-- Jenis transaksi -->
            <div class="grid grid-cols-2 gap-2">
                <button
                    class="rounded-2xl py-3.5 text-sm font-bold transition"
                    :class="
                        type === 'masuk'
                            ? 'bg-teal-700 text-white'
                            : 'bg-white text-slate-500 active:bg-slate-50'
                    "
                    @click="selectType('masuk')"
                >
                    UANG MASUK
                </button>
                <button
                    class="rounded-2xl py-3.5 text-sm font-bold transition"
                    :class="
                        type === 'keluar' ? 'bg-red-600 text-white' : 'bg-white text-slate-500 active:bg-slate-50'
                    "
                    @click="selectType('keluar')"
                >
                    UANG KELUAR
                </button>
            </div>

            <!-- Kategori -->
            <div class="-mx-3 overflow-x-auto px-3">
                <div class="flex w-max gap-2">
                    <button
                        v-for="category in visibleCategories"
                        :key="category.id"
                        class="shrink-0 rounded-full px-3.5 py-2 text-xs font-semibold transition"
                        :class="
                            categoryId === category.id
                                ? 'bg-slate-900 text-white'
                                : 'bg-white text-slate-600 active:bg-slate-50'
                        "
                        @click="categoryId = categoryId === category.id ? null : category.id"
                    >
                        {{ category.name }}
                    </button>
                </div>
            </div>

            <input
                v-model="note"
                type="text"
                maxlength="255"
                placeholder="Keterangan (opsional)"
                class="w-full rounded-2xl border-0 bg-white px-4 py-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:ring-2 focus:ring-teal-600/20"
            />

            <p v-if="form.errors.amount" class="px-1 text-sm text-red-600">{{ form.errors.amount }}</p>
            <p v-if="form.errors.category_id" class="px-1 text-sm text-red-600">{{ form.errors.category_id }}</p>
            <p v-if="form.errors.occurred_on" class="px-1 text-sm text-red-600">{{ form.errors.occurred_on }}</p>

            <!-- Numpad -->
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="key in keys"
                    :key="key"
                    class="rounded-2xl bg-white py-4 text-xl font-semibold text-slate-900 tabular-nums transition active:bg-slate-200"
                    @click="press(key)"
                >
                    {{ key === 'back' ? '⌫' : key }}
                </button>
            </div>

            <button
                :disabled="amount < 1 || form.processing"
                class="w-full rounded-2xl py-4 text-base font-bold text-white transition disabled:bg-slate-300"
                :class="[
                    amount < 1 || form.processing ? '' : type === 'masuk' ? 'bg-teal-700' : 'bg-red-600',
                ]"
                @click="submit"
            >
                {{ form.processing ? 'Menyimpan…' : 'SIMPAN' }}
            </button>

            <!-- Transaksi terakhir hari ini -->
            <div v-if="recent.length" class="rounded-2xl bg-white p-3">
                <p class="mb-2 px-1 text-xs font-semibold text-slate-500">Terakhir hari ini</p>
                <ul class="divide-y divide-slate-100">
                    <li v-for="item in recent" :key="item.id" class="flex items-center gap-3 px-1 py-2.5">
                        <span
                            class="size-2 shrink-0 rounded-full"
                            :class="item.type === 'masuk' ? 'bg-teal-600' : 'bg-red-500'"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-slate-900">
                                {{ item.category ?? 'Tanpa kategori' }}
                            </p>
                            <p v-if="item.note" class="truncate text-xs text-slate-500">{{ item.note }}</p>
                        </div>
                        <p
                            class="shrink-0 text-sm font-semibold tabular-nums"
                            :class="item.type === 'masuk' ? 'text-teal-700' : 'text-red-600'"
                        >
                            {{ item.type === 'masuk' ? '+' : '−' }}{{ formatRupiah(item.amount) }}
                        </p>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
