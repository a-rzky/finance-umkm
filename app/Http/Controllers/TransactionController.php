<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\TransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\ReportService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly ReportService $reportService,
    ) {}

    /** Layar kasir: input cepat nominal + ringkasan hari ini. */
    public function create(): Response
    {
        $today = today()->toDateString();

        return Inertia::render('Kasir', [
            'categories' => $this->categoryOptions(),
            'today' => $this->reportService->summaryFor($today),
            'todayDate' => $today,
            'recent' => Transaction::query()
                ->with('category:id,name')
                ->where('occurred_on', $today)
                ->latest('id')
                ->limit(8)
                ->get()
                ->map($this->present(...)),
        ]);
    }

    public function store(TransactionRequest $request): RedirectResponse
    {
        $this->transactionService->record($request->validated(), $request->user());

        return back()->with('success', 'Transaksi tersimpan.');
    }

    /** Riwayat transaksi, dikelompokkan per hari. */
    public function index(Request $request): Response
    {
        $transactions = Transaction::query()
            ->with('category:id,name')
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Riwayat', [
            'categories' => $this->categoryOptions(),
            'days' => $transactions
                ->getCollection()
                ->groupBy(fn (Transaction $trx) => $trx->occurred_on->toDateString())
                ->map(fn ($items, $date) => [
                    'date' => $date,
                    'masuk' => $items->where('type', TransactionType::Masuk)->sum('amount'),
                    'keluar' => $items->where('type', TransactionType::Keluar)->sum('amount'),
                    'items' => $items->map($this->present(...))->values(),
                ])
                ->values(),
            'pagination' => [
                'current' => $transactions->currentPage(),
                'last' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->transactionService->update($transaction, $request->validated());

        return back()->with('success', 'Transaksi diperbarui.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->transactionService->delete($transaction);

        return back()->with('success', 'Transaksi dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Transaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'occurred_on' => $transaction->occurred_on->toDateString(),
            'note' => $transaction->note,
            'category_id' => $transaction->category_id,
            'category' => $transaction->category?->name,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function categoryOptions()
    {
        return Category::query()
            ->select(['id', 'name', 'type'])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
            ]);
    }
}
