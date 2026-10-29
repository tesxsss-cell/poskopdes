<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (config('pos.increment', 4) < 4) {
            $lowStocks = config('pos.increment', 4) >= 2 && $request->user()->isPengelola()
                ? \App\Models\Stock::with('product')->lowStock()->get() : collect();
            return view('increment', compact('lowStocks'));
        }
        $reports = app(ReportService::class);
        $inventory = app(InventoryService::class);
        if ($request->user()->isKasir()) {
            return redirect()->route('pos.index');
        }
        $data = $request->validate(['dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date', 'after_or_equal:dari']]);
        $from = $data['dari'] ?? now()->startOfMonth()->toDateString();
        $to = $data['sampai'] ?? now()->toDateString();
        $id = $request->user()->branch_id;

        return view('dashboard', ['from' => $from, 'to' => $to, 'summary' => $reports->summary($id, $from, $to),
            'lowStocks' => $reports->lowStocks($id), 'inventoryValue' => $inventory->inventoryValue($id),
            'shifts' => $reports->shiftRecap($id, $from, $to)]);
    }
}
