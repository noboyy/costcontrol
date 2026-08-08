<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengguna extends Model
{
    use HasFactory;

    protected $table = 'pengguna';
    protected $primaryKey = 'id_pengguna';

    protected $fillable = [
        'id_perusahaan',
        'nama_lengkap',
        'no_hp',
        'alamat',
        'jabatan',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function akun()
    {
        return $this->hasOne(Akun::class, 'id_pengguna', 'id_pengguna');
    }

    public function projectsAsAdmin()
    {
        return $this->belongsToMany(Project::class, 'project_admin', 'id_pengguna', 'id_project');
    }
}
