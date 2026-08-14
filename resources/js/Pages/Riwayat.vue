<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { formatDayLabel, formatRupiah } from '../rupiah';

const props = defineProps({
    categories: { type: Array, required: true },
    days: { type: Array, required: true },
    pagination: { type: Object, required: true },
});

const todayIso = new Date().toISOString().slice(0, 10);

const editing = ref(null);
const confirmingDelete = ref(null);

const form = useForm({
    type: 'masuk',
    amount: 0,
    occurred_on: todayIso,
    note: '',
    category_id: null,
});

const visibleCategories = computed(() => props.categories.filter((c) => c.type === form.type));

const openEdit = (item) => {
    editing.value = item;
    form.clearErrors();
    form.defaults({
        type: item.type,
        amount: item.amount,
        occurred_on: item.occurred_on,
        note: item.note ?? '',
        category_id: item.category_id,
    });
    form.reset();
};

const closeEdit = () => {
    editing.value = null;
};

const save = () => {
    form.transform((data) => ({ ...data, note: data.note || null }))
        .put(`/transaksi/${editing.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeEdit(),
        });
};

const remove = (item) => {
    router.delete(`/transaksi/${item.id}`, {
        preserveScroll: true,
        onSuccess: () => (confirmingDelete.value = null),
    });
};

const goToPage = (pageNumber) => {
    router.get('/riwayat', { page: pageNumber }, { preserveScroll: true });
};

const inputClass =
    'w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20';
</script>

<template>
    <Head title="Riwayat" />

    <AppLayout>
        <div class="space-y-3 p-3">
            <div v-if="!days.length" class="rounded-2xl bg-white p-8 text-center">
                <p class="text-sm text-slate-500">Belum ada transaksi.</p>
            </div>

            <section v-for="day in days" :key="day.date" class="overflow-hidden rounded-2xl bg-white">
                <header class="flex items-baseline justify-between gap-2 border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">{{ formatDayLabel(day.date, todayIso) }}</h2>
                    <p class="shrink-0 text-xs tabular-nums">
                        <span class="text-teal-700">{{ formatRupiah(day.masuk) }}</span>
                        <span class="mx-1 text-slate-300">/</span>
                        <span class="text-red-600">{{ formatRupiah(day.keluar) }}</span>
                    </p>
                </header>

                <ul class="divide-y divide-slate-100">
                    <li v-for="item in day.items" :key="item.id">
                        <div class="flex items-center gap-3 px-4 py-3">
                            <span
                                class="size-2 shrink-0 rounded-full"
                                :class="item.type === 'masuk' ? 'bg-teal-600' : 'bg-red-500'"
                            />
                            <button class="min-w-0 flex-1 text-left" @click="openEdit(item)">
                                <p class="truncate text-sm text-slate-900">
                                    {{ item.category ?? 'Tanpa kategori' }}
                                </p>
                                <p v-if="item.note" class="truncate text-xs text-slate-500">{{ item.note }}</p>
                            </button>
                            <p
                                class="shrink-0 text-sm font-semibold tabular-nums"
                                :class="item.type === 'masuk' ? 'text-teal-700' : 'text-red-600'"
                            >
                                {{ item.type === 'masuk' ? '+' : '−' }}{{ formatRupiah(item.amount) }}
                            </p>
                            <button
                                class="shrink-0 rounded-lg px-2 py-1 text-xs text-slate-400 transition active:bg-slate-100"
                                @click="confirmingDelete = confirmingDelete === item.id ? null : item.id"
                            >
                                ⋯
                            </button>
                        </div>

                        <div v-if="confirmingDelete === item.id" class="flex gap-2 bg-slate-50 px-4 py-2.5">
                            <p class="flex-1 text-xs text-slate-600">Hapus transaksi ini?</p>
                            <button
                                class="rounded-lg px-3 py-1 text-xs font-semibold text-slate-500"
                                @click="confirmingDelete = null"
                            >
                                Batal
                            </button>
                            <button
                                class="rounded-lg bg-red-600 px-3 py-1 text-xs font-semibold text-white"
                                @click="remove(item)"
                            >
                                Hapus
                            </button>
                        </div>
                    </li>
                </ul>
            </section>

            <div v-if="pagination.last > 1" class="flex items-center justify-between gap-3 px-1">
                <button
                    :disabled="pagination.current <= 1"
                    class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-700 disabled:opacity-40"
                    @click="goToPage(pagination.current - 1)"
                >
                    Sebelumnya
                </button>
                <span class="text-xs text-slate-500">
                    Halaman {{ pagination.current }} dari {{ pagination.last }}
                </span>
                <button
                    :disabled="pagination.current >= pagination.last"
                    class="rounded-xl bg-white px-4 py-2 text-sm font-medium text-slate-700 disabled:opacity-40"
                    @click="goToPage(pagination.current + 1)"
                >
                    Berikutnya
                </button>
            </div>
        </div>

        <!-- Panel ubah transaksi -->
        <Teleport to="body">
            <div v-if="editing" class="fixed inset-0 z-40 flex items-end justify-center bg-slate-900/40 p-0 sm:items-center sm:p-4">
                <div class="w-full max-w-lg rounded-t-3xl bg-white p-5 sm:rounded-3xl">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-slate-900">Ubah transaksi</h2>
                        <button class="rounded-lg px-2 py-1 text-sm text-slate-400" @click="closeEdit">Tutup</button>
                    </div>

                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                class="rounded-xl py-2.5 text-xs font-bold transition"
                                :class="
                                    form.type === 'masuk'
                                        ? 'bg-teal-700 text-white'
                                        : 'bg-slate-100 text-slate-500'
                                "
                                @click="((form.type = 'masuk'), (form.category_id = null))"
                            >
                                UANG MASUK
                            </button>
                            <button
                                class="rounded-xl py-2.5 text-xs font-bold transition"
                                :class="
                                    form.type === 'keluar' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-500'
                                "
                                @click="((form.type = 'keluar'), (form.category_id = null))"
                            >
                                UANG KELUAR
                            </button>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Nominal</label>
                            <input v-model.number="form.amount" type="number" min="1" :class="inputClass" />
                            <p v-if="form.errors.amount" class="mt-1 text-xs text-red-600">{{ form.errors.amount }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Tanggal</label>
                            <input v-model="form.occurred_on" type="date" :max="todayIso" :class="inputClass" />
                            <p v-if="form.errors.occurred_on" class="mt-1 text-xs text-red-600">
                                {{ form.errors.occurred_on }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Kategori</label>
                            <select v-model="form.category_id" :class="inputClass">
                                <option :value="null">Tanpa kategori</option>
                                <option v-for="c in visibleCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">
                                {{ form.errors.category_id }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Keterangan</label>
                            <input v-model="form.note" type="text" maxlength="255" :class="inputClass" />
                        </div>

                        <button
                            :disabled="form.processing"
                            class="w-full rounded-xl bg-slate-900 py-3 text-sm font-bold text-white disabled:opacity-60"
                            @click="save"
                        >
                            {{ form.processing ? 'Menyimpan…' : 'Simpan perubahan' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
