<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    use HasFactory;

    protected $table = 'income_category';

    protected $primaryKey = 'id_income_category';

    protected $fillable = [
        'id_perusahaan',
        'kode',
        'nama',
        'icon',
        'warna',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function incomeTypes()
    {
        return $this->hasMany(IncomeType::class, 'kategori', 'kode');
    }

    public function scopeForCompany($query, ?int $companyId)
    {
        return $query->where('id_perusahaan', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
