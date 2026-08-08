<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCostPlan extends Model
{
    use HasFactory;

    protected $table = 'project_cost_plan';

    protected $fillable = [
        'id_perusahaan',
        'id_project',
        'id_cost_type',
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

    public function costType()
    {
        return $this->belongsTo(CostType::class, 'id_cost_type', 'id_cost_type');
    }
}
