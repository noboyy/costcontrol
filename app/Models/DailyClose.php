<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyClose extends Model
{
    use HasFactory;

    protected $table = 'daily_close';

    protected $primaryKey = 'id_daily_close';

    protected $fillable = [
        'id_perusahaan',
        'id_project',
        'tanggal',
        'total_income',
        'total_cost_cash',
        'total_cogs',
        'total_ops',
        'total_fixed_prorate',
        'total_cost_economic',
        'margin_cash',
        'margin_economic',
        'cogs_ratio',
        'daily_budget',
        'over_budget',
        'leak_alert',
        'notes',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_income' => 'decimal:2',
        'total_cost_cash' => 'decimal:2',
        'total_cogs' => 'decimal:2',
        'total_ops' => 'decimal:2',
        'total_fixed_prorate' => 'decimal:2',
        'total_cost_economic' => 'decimal:2',
        'margin_cash' => 'decimal:2',
        'margin_economic' => 'decimal:2',
        'cogs_ratio' => 'decimal:4',
        'daily_budget' => 'decimal:2',
        'over_budget' => 'boolean',
        'leak_alert' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }

    public function closer()
    {
        return $this->belongsTo(Akun::class, 'closed_by', 'id_akun');
    }
}
