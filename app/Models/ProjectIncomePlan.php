<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectIncomePlan extends Model
{
    use HasFactory;

    protected $table = 'project_income_plan';

    protected $fillable = [
        'id_perusahaan',
        'id_project',
        'id_income_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }

    public function incomeType()
    {
        return $this->belongsTo(IncomeType::class, 'id_income_type', 'id_income_type');
    }
}
