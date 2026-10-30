<?php
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
class InventoryController extends Controller
{
    public function stockCard(Request $request)
    {
        $data = $request->validate(['barang' => ['nullable', 'integer', 'exists:products,id'], 'dari' => ['nullable', 'date'], 'sampai' => ['nullable', 'date', 'after_or_equal:dari']]);
        $from = $data['dari'] ?? now()->startOfMonth()->toDateString();
        $to = $data['sampai'] ?? now()->toDateString();
        $productId = $data['barang'] ?? null;
        $products = Product::orderBy('name')->get();
        $branchId = $request->user()->branch_id;
        $movements = StockMovement::with(['product', 'user'])->where('branch_id', $branchId)
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->orderBy('created_at')->orderBy('id')->get();
        return view('reports.stock-card', compact('from', 'to', 'productId', 'products', 'movements', 'branchId'));
    }
}
