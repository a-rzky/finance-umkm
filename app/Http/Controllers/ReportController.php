<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Services\ReportService;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function index(ReportRequest $request): Response
    {
        ['from' => $from, 'until' => $until] = $request->validated();

        $days = $this->reportService->daily($from, $until);

        return Inertia::render('Rekap', [
            'from' => $from,
            'until' => $until,
            'days' => $days,
            'totals' => [
                'masuk' => $days->sum('masuk'),
                'keluar' => $days->sum('keluar'),
                'saldo' => $days->sum('saldo'),
            ],
            'categories' => $this->reportService->byCategory($from, $until),
        ]);
    }
}
