<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { formatDayLabel, formatRupiah } from '../rupiah';

const props = defineProps({
    from: { type: String, required: true },
    until: { type: String, required: true },
    days: { type: Array, required: true },
    totals: { type: Object, required: true },
    categories: { type: Array, required: true },
});

const todayIso = new Date().toISOString().slice(0, 10);

const from = ref(props.from);
const until = ref(props.until);

const applyRange = () => {
    router.get('/rekap', { from: from.value, until: until.value }, { preserveScroll: true });
};

const shiftDays = (count) => {
    const start = new Date(`${todayIso}T00:00:00`);
    start.setDate(start.getDate() - (count - 1));

    from.value = start.toISOString().slice(0, 10);
    until.value = todayIso;
    applyRange();
};

const thisMonth = () => {
    from.value = `${todayIso.slice(0, 7)}-01`;
    until.value = todayIso;
    applyRange();
};

// Skala batang memakai nilai harian tertinggi agar perbandingan antar hari terbaca.
const maxDaily = computed(() => Math.max(1, ...props.days.map((d) => Math.max(d.masuk, d.keluar))));

const spending = computed(() =>
    props.categories.filter((c) => c.type === 'keluar').slice(0, 6)
);
const maxSpending = computed(() => Math.max(1, ...spending.value.map((c) => c.total)));
</script>

<template>
    <Head title="Rekap" />

    <AppLayout>
        <div class="space-y-3 p-3">
            <!-- Pilihan rentang -->
            <div class="space-y-3 rounded-2xl bg-white p-3">
                <div class="flex gap-2">
                    <button
                        class="flex-1 rounded-xl bg-slate-100 py-2 text-xs font-semibold text-slate-700 active:bg-slate-200"
                        @click="shiftDays(1)"
                    >
                        Hari ini
                    </button>
                    <button
                        class="flex-1 rounded-xl bg-slate-100 py-2 text-xs font-semibold text-slate-700 active:bg-slate-200"
                        @click="shiftDays(7)"
                    >
                        7 hari
                    </button>
                    <button
                        class="flex-1 rounded-xl bg-slate-100 py-2 text-xs font-semibold text-slate-700 active:bg-slate-200"
                        @click="shiftDays(30)"
                    >
                        30 hari
                    </button>
                    <button
                        class="flex-1 rounded-xl bg-slate-100 py-2 text-xs font-semibold text-slate-700 active:bg-slate-200"
                        @click="thisMonth"
                    >
                        Bulan ini
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        v-model="from"
                        type="date"
                        :max="todayIso"
                        class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                        @change="applyRange"
                    />
                    <span class="text-xs text-slate-400">s/d</span>
                    <input
                        v-model="until"
                        type="date"
                        :max="todayIso"
                        class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-900 outline-none focus:border-teal-600"
                        @change="applyRange"
                    />
                </div>
            </div>

            <!-- Total rentang -->
            <div class="rounded-2xl bg-slate-900 p-4">
                <p class="text-xs font-medium text-slate-400">Sisa bersih</p>
                <p
                    class="mt-1 text-3xl font-bold tabular-nums"
                    :class="totals.saldo < 0 ? 'text-red-400' : 'text-white'"
                >
                    {{ formatRupiah(totals.saldo) }}
                </p>
                <div class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-700 pt-3">
                    <div>
                        <p class="text-[11px] text-slate-400">Uang masuk</p>
                        <p class="truncate text-sm font-semibold text-teal-400">{{ formatRupiah(totals.masuk) }}</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-400">Uang keluar</p>
                        <p class="truncate text-sm font-semibold text-red-400">{{ formatRupiah(totals.keluar) }}</p>
                    </div>
                </div>
            </div>

            <!-- Rekap per hari -->
            <section class="overflow-hidden rounded-2xl bg-white">
                <h2 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">Per hari</h2>

                <p v-if="!days.length" class="px-4 py-8 text-center text-sm text-slate-500">
                    Belum ada transaksi di rentang ini.
                </p>

                <ul v-else class="divide-y divide-slate-100">
                    <li v-for="day in days" :key="day.date" class="px-4 py-3">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="truncate text-sm font-medium text-slate-900">
                                {{ formatDayLabel(day.date, todayIso) }}
                            </p>
                            <p
                                class="shrink-0 text-sm font-semibold tabular-nums"
                                :class="day.saldo < 0 ? 'text-red-600' : 'text-slate-900'"
                            >
                                {{ formatRupiah(day.saldo) }}
                            </p>
                        </div>

                        <div class="mt-2 space-y-1">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-teal-600"
                                        :style="{ width: `${(day.masuk / maxDaily) * 100}%` }"
                                    />
                                </div>
                                <span class="w-24 shrink-0 text-right text-[11px] tabular-nums text-teal-700">
                                    {{ formatRupiah(day.masuk) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-red-500"
                                        :style="{ width: `${(day.keluar / maxDaily) * 100}%` }"
                                    />
                                </div>
                                <span class="w-24 shrink-0 text-right text-[11px] tabular-nums text-red-600">
                                    {{ formatRupiah(day.keluar) }}
                                </span>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Ke mana uang keluar -->
            <section v-if="spending.length" class="overflow-hidden rounded-2xl bg-white">
                <h2 class="border-b border-slate-100 px-4 py-3 text-sm font-semibold text-slate-900">
                    Uang keluar terbesar
                </h2>
                <ul class="divide-y divide-slate-100">
                    <li v-for="item in spending" :key="item.category" class="px-4 py-3">
                        <div class="flex items-baseline justify-between gap-2">
                            <p class="truncate text-sm text-slate-700">{{ item.category }}</p>
                            <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-900">
                                {{ formatRupiah(item.total) }}
                            </p>
                        </div>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full bg-slate-400"
                                :style="{ width: `${(item.total / maxSpending) * 100}%` }"
                            />
                        </div>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
