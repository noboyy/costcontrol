<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    use HasFactory;

    public const MODULE_ALL = 'all';

    public const MODULE_PROJECT = 'project';

    protected $table = 'perusahaan';

    protected $primaryKey = 'id_perusahaan';

    protected $fillable = [
        'nama_perusahaan',
        'alamat_lengkap',
        'owner',
        'module',
        'opening_balance',
        'mode_pajak',
        'tarif_pph_omzet',
        'tarif_pph_laba',
        'pajak_aktif',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'tarif_pph_omzet' => 'decimal:2',
        'tarif_pph_laba' => 'decimal:2',
        'pajak_aktif' => 'boolean',
    ];

    public const PAJAK_FINAL_OMZET = 'final_omzet';

    public const PAJAK_BADAN_LABA = 'badan_laba';

    public function module(): string
    {
        return $this->module ?: self::MODULE_ALL;
    }

    public function isModuleAll(): bool
    {
        return $this->module() === self::MODULE_ALL;
    }

    public function isModuleProject(): bool
    {
        return $this->module() === self::MODULE_PROJECT;
    }

    /**
     * Kolom project.mode sudah dihapus — semua entitas adalah keberangkatan,
     * filter module jadi no-op.
     */
    public static function filterByModule($query, ?string $module = null): void
    {
        // no-op
    }

    public function pengguna()
    {
        return $this->hasMany(Pengguna::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'id_perusahaan', 'id_perusahaan');
    }
}
