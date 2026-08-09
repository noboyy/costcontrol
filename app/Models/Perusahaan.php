<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    use HasFactory;

    public const MODULE_ALL = 'all';

    public const MODULE_PROJECT = 'project';

    public const MODULE_UMKM = 'umkm';

    protected $table = 'perusahaan';

    protected $primaryKey = 'id_perusahaan';

    protected $fillable = [
        'nama_perusahaan',
        'alamat_lengkap',
        'owner',
        'module',
    ];

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

    public function isModuleUmkm(): bool
    {
        return $this->module() === self::MODULE_UMKM;
    }

    public static function filterByModule($query, ?string $module = null): void
    {
        $module = $module ?: self::MODULE_ALL;

        if ($module === self::MODULE_PROJECT) {
            $query->where(function ($q) {
                $q->where('mode', self::MODULE_PROJECT)->orWhereNull('mode');
            });
        } elseif ($module === self::MODULE_UMKM) {
            $query->where('mode', self::MODULE_UMKM);
        }
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
