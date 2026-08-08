<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostType extends Model
{
    use HasFactory;

    protected $table = 'cost_type';
    protected $primaryKey = 'id_cost_type';

    protected $fillable = [
        'id_perusahaan',
        'kode',
        'nama',
        'kategori',
        'default_unit',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function costEntries()
    {
        return $this->hasMany(CostEntry::class, 'id_cost_type', 'id_cost_type');
    }
}
