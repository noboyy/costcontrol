<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'asset';

    protected $primaryKey = 'id_asset';

    protected $fillable = [
        'id_perusahaan',
        'nama_asset',
        'nilai_asset',
        'keterangan',
        'gambar',
        'status',
        'alasan_jual',
        'nilai_jual',
        'tanggal_jual',
    ];

    protected $casts = [
        'nilai_asset' => 'decimal:2',
        'nilai_jual' => 'decimal:2',
        'tanggal_jual' => 'date',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(AssetMaintenance::class, 'id_asset', 'id_asset');
    }

    public function isSold(): bool
    {
        return $this->status === 'Dijual';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'Ada';
    }
}
