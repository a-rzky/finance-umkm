<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Total masuk, keluar, dan selisihnya pada satu tanggal.
     *
     * @return array{masuk: int, keluar: int, saldo: int}
     */
    public function summaryFor(string $date): array
    {
        return $this->daily($date, $date)->first() ?? [
            'date' => $date,
            'masuk' => 0,
            'keluar' => 0,
            'saldo' => 0,
        ];
    }

    /**
     * Rekap per hari dalam satu rentang tanggal.
     *
     * Agregasi dikerjakan database, bukan dengan mengambil semua baris lalu
     * dijumlahkan di PHP — jumlah transaksi bisa besar seiring waktu.
     *
     * @return Collection<int, array{date: string, masuk: int, keluar: int, saldo: int}>
     */
    public function daily(string $from, string $until): Collection
    {
        return Transaction::query()
            ->betweenDates($from, $until)
            ->selectRaw('occurred_on')
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) AS masuk', [TransactionType::Masuk->value])
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) AS keluar', [TransactionType::Keluar->value])
            ->groupBy('occurred_on')
            ->orderByDesc('occurred_on')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->occurred_on->toDateString(),
                'masuk' => (int) $row->masuk,
                'keluar' => (int) $row->keluar,
                'saldo' => (int) $row->masuk - (int) $row->keluar,
            ]);
    }

    /**
     * Rincian per kategori dalam satu rentang tanggal, untuk menjawab
     * "uang keluar paling banyak habis ke mana".
     *
     * @return Collection<int, array{category: string, type: string, total: int}>
     */
    public function byCategory(string $from, string $until): Collection
    {
        return Transaction::query()
            ->betweenDates($from, $until)
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->selectRaw('COALESCE(categories.name, ?) AS category', ['Tanpa kategori'])
            // Dialiaskan agar tidak terkena cast enum milik kolom "type".
            ->selectRaw('transactions.type AS type_value')
            ->selectRaw('SUM(transactions.amount) AS total')
            ->groupBy('category', 'transactions.type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'type' => $row->type_value,
                'total' => (int) $row->total,
            ]);
    }
}
