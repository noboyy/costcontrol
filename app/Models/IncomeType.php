<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeType extends Model
{
    use HasFactory;

    protected $table = 'income_type';
    protected $primaryKey = 'id_income_type';

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

    public function incomeEntries()
    {
        return $this->hasMany(IncomeEntry::class, 'id_income_type', 'id_income_type');
    }
}
