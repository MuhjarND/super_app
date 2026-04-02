<?php

namespace App\Http\Controllers;

use App\InventoryMaintenanceTransaction;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersediaanLaporanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $data = $this->buildReportData($request);

        return view('persediaan.reports.index', $data);
    }

    public function pdf(Request $request)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $data = $this->buildReportData($request);
        $pdf = PDF::loadView('persediaan.reports.pdf', $data)->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-perawatan-alat-dan-mesin.pdf');
    }

    public function excel(Request $request)
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $data = $this->buildReportData($request);
        $content = view('persediaan.reports.excel', $data)->render();

        return response($content)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="laporan-perawatan-alat-dan-mesin.xls"');
    }

    protected function buildReportData(Request $request)
    {
        $query = InventoryMaintenanceTransaction::with(['item', 'detail', 'creator'])->latest('transaction_date');

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('transaction_date', [$request->from, $request->to]);
        }

        $transactions = (clone $query)->get();
        $totalAmount = (float) $transactions->sum('amount');

        $byItem = InventoryMaintenanceTransaction::select('inventory_item_id', DB::raw('SUM(amount) as total_amount'))
            ->with('item')
            ->when($request->filled('from') && $request->filled('to'), function ($builder) use ($request) {
                $builder->whereBetween('transaction_date', [$request->from, $request->to]);
            })
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'transactions' => $transactions,
            'totalAmount' => $totalAmount,
            'byItem' => $byItem,
            'filters' => [
                'from' => $request->from,
                'to' => $request->to,
            ],
        ];
    }
}
