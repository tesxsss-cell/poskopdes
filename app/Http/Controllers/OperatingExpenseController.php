<?php

namespace App\Http\Controllers;

use App\Models\OperatingExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperatingExpenseController extends Controller
{
    public function create(Request $request): View
    {
        $this->branchId($request);
        return view('expenses.form', ['expense' => new OperatingExpense(), 'categories' => OperatingExpense::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $expense = OperatingExpense::create([...$this->validated($request), 'branch_id' => $this->branchId($request),
            'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        return redirect()->route('reports.complete', ['bulan' => $expense->expense_date->format('Y-m')])->with('status', 'Biaya dicatat.');
    }

    public function edit(Request $request, OperatingExpense $expense): View
    {
        $this->authorizeExpense($request, $expense);
        return view('expenses.form', ['expense' => $expense, 'categories' => OperatingExpense::CATEGORIES]);
    }

    public function update(Request $request, OperatingExpense $expense): RedirectResponse
    {
        $this->authorizeExpense($request, $expense);
        $expense->update([...$this->validated($request), 'updated_by' => $request->user()->id]);
        return redirect()->route('reports.complete', ['bulan' => $expense->expense_date->format('Y-m')])->with('status', 'Biaya diperbarui.');
    }

    public function destroy(Request $request, OperatingExpense $expense): RedirectResponse
    {
        $this->authorizeExpense($request, $expense);
        $expense->update(['deleted_by' => $request->user()->id]);
        $expense->delete();
        return redirect()->route('reports.complete', ['bulan' => $expense->expense_date->format('Y-m')])->with('status', 'Biaya dihapus dari laporan; catatan tersimpan di basis data.');
    }

    private function branchId(Request $request): int
    {
        $id = $request->user()->branch_id;
        abort_unless($id, 403);
        return (int) $id;
    }

    private function authorizeExpense(Request $request, OperatingExpense $expense): void
    {
        abort_unless((int) $expense->branch_id === $this->branchId($request), 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date_format:Y-m-d'],
            'category' => ['required', Rule::in(array_keys(OperatingExpense::CATEGORIES))],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:999999999999'],
        ]);
    }
}
