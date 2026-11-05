<?php

namespace App\Http\Controllers;

use App\Models\OperatingExpense;
use App\Support\TablePage;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompleteReportController extends Controller
{
    public function __invoke(Request $request, ReportService $reports): View
    {
        $data = $request->validate([
            'bulan' => ['nullable', 'date_format:Y-m'],
            'dari' => ['nullable', 'required_with:sampai', 'date_format:Y-m-d'],
            'sampai' => ['nullable', 'required_with:dari', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'cetak' => ['nullable', 'in:1'],
        ]);
        $branchId = $request->user()->branch_id;
        abort_unless($branchId, 403);
        if (! empty($data['bulan'])) {
            $month = Carbon::createFromFormat('!Y-m', $data['bulan']);
            $from = $month->copy()->startOfMonth()->toDateString();
            $to = $month->copy()->endOfMonth()->toDateString();
        } else {
            $from = $data['dari'] ?? now()->startOfMonth()->toDateString();
            $to = $data['sampai'] ?? now()->toDateString();
        }
        $print = ($data['cetak'] ?? null) === '1';
        $summary = $reports->summary($branchId, $from, $to);
        $series = $reports->dailySeries($branchId, $from, $to);
        $expenseQuery = OperatingExpense::query()->where('branch_id', $branchId)->whereDate('expense_date', '>=', $from)->whereDate('expense_date', '<=', $to);
        $expenseTotal = (float) (clone $expenseQuery)->sum('amount');
        $expenseCategories = (clone $expenseQuery)->select('category')->selectRaw('SUM(amount) AS total')
            ->groupBy('category')->orderBy('category')->get();
        $expenseRows = (clone $expenseQuery)->orderByDesc('expense_date')->orderByDesc('id');

        // Pergerakan dan penjualan diagregasi terpisah untuk menghindari penggandaan baris.
        $movements = DB::table('stock_movements')->where('branch_id', $branchId)
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) AS qty_in")
            ->selectRaw("SUM(CASE WHEN quantity < 0 THEN -quantity ELSE 0 END) AS qty_out")
            ->groupBy('product_id');
        $movementRows = DB::table('stock_movements as sm')
            ->join('products as p', 'p.id', '=', 'sm.product_id')
            ->leftJoin('users as u', 'u.id', '=', 'sm.user_id')
            ->where('sm.branch_id', $branchId)
            ->whereDate('sm.created_at', '>=', $from)->whereDate('sm.created_at', '<=', $to)
            ->select('sm.created_at', 'sm.type', 'sm.quantity', 'sm.balance_after', 'sm.cost_total',
                'sm.note', 'p.sku', 'p.name', 'p.unit', 'u.name as user_name')
            ->orderByDesc('sm.created_at')->orderByDesc('sm.id');
        $sold = DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.branch_id', $branchId)->where('sales.status', 'selesai')
            ->whereDate('sales.sold_at', '>=', $from)->whereDate('sales.sold_at', '<=', $to)
            ->select('sale_items.product_id')->selectRaw('SUM(sale_items.subtotal) AS revenue')
            ->selectRaw('SUM(sale_items.cogs_total) AS cogs')->selectRaw('SUM(sale_items.gross_profit) AS gross_profit')
            ->groupBy('sale_items.product_id');
        $products = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('stocks', function ($join) use ($branchId) {
                $join->on('stocks.product_id', '=', 'products.id')->where('stocks.branch_id', '=', $branchId);
            })
            ->leftJoinSub($movements, 'movements', 'movements.product_id', '=', 'products.id')
            ->leftJoinSub($sold, 'sold', 'sold.product_id', '=', 'products.id')
            ->select('products.id', 'products.sku', 'products.barcode', 'products.name', 'products.unit',
                'products.sell_price', 'products.is_active', 'products.deleted_at', 'categories.name AS category')
            ->selectRaw('COALESCE(stocks.quantity, 0) AS stock_now, COALESCE(movements.qty_in, 0) AS qty_in, COALESCE(movements.qty_out, 0) AS qty_out')
            ->selectRaw('COALESCE(sold.revenue, 0) AS revenue, COALESCE(sold.cogs, 0) AS cogs, COALESCE(sold.gross_profit, 0) AS gross_profit')
            ->orderBy('products.name')->orderBy('products.id');

        return view('reports.complete', [
            'from' => $from, 'to' => $to, 'month' => $data['bulan'] ?? '', 'print' => $print,
            'summary' => $summary, 'series' => $print ? $series : TablePage::make($series, 'sales_page'), 'expenseTotal' => $expenseTotal, 'expenseCategories' => $expenseCategories,
            'expenses' => $print ? $expenseRows->get() : $expenseRows->paginate(10, ['*'], 'expense_page')->withQueryString(),
            'products' => $print ? $products->get() : $products->paginate(10, ['*'], 'product_page')->withQueryString(),
            'movementRows' => $print ? $movementRows->get() : $movementRows->paginate(10, ['*'], 'movement_page')->withQueryString(),
            'categories' => OperatingExpense::CATEGORIES,
            'branch' => $request->user()->branch,
        ]);
    }
}
