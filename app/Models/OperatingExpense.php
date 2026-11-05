<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperatingExpense extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'gaji' => 'Gaji dan honor',
        'listrik_air' => 'Listrik, air dan internet',
        'sewa' => 'Sewa',
        'transportasi' => 'Transportasi',
        'pemeliharaan' => 'Pemeliharaan',
        'penyusutan' => 'Penyusutan',
        'pajak' => 'Pajak dan perizinan',
        'lainnya' => 'Biaya lainnya',
    ];

    protected $fillable = ['branch_id', 'expense_date', 'category', 'description', 'amount', 'created_by', 'updated_by', 'deleted_by'];

    protected function casts(): array
    {
        return ['expense_date' => 'date', 'amount' => 'decimal:2'];
    }
}
