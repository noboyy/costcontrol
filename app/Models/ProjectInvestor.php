<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectInvestor extends Model
{
    protected $table = 'project_investor';

    protected $fillable = [
        'id_project',
        'id_akun',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }

    public function akun()
    {
        return $this->belongsTo(Akun::class, 'id_akun', 'id_akun');
    }
}
