<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Identitas satu gerai; nama tabel dipertahankan agar relasi source tetap kompatibel. */
class Branch extends Model
{
    protected $fillable = ['code', 'name', 'phone', 'address', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function stocks(): HasMany { return $this->hasMany(Stock::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
}
