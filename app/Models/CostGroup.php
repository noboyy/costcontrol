<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostGroup extends Model
{
    use HasFactory;

    protected $table = 'cost_group';

    protected $primaryKey = 'id_cost_group';

    protected $fillable = [
        'id_perusahaan',
        'kode',
        'nama',
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

    public function costCategories()
    {
        return $this->hasMany(CostCategory::class, 'kelompok', 'kode');
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