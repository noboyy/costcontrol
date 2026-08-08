<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedCost extends Model
{
    use HasFactory;

    protected $table = 'fixed_cost';
    protected $primaryKey = 'id_fixed_cost';

    protected $fillable = [
        'id_perusahaan',
        'id_project',
        'id_cost_type',
        'nama',
        'amount_monthly',
        'start_date',
        'end_date',
        'is_active',
        'catatan',
    ];

    protected $casts = [
        'amount_monthly' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'id_project', 'id_project');
    }

    public function costType()
    {
        return $this->belongsTo(CostType::class, 'id_cost_type', 'id_cost_type');
    }

    public function isActiveOn(Carbon|string $date): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $d = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();
        if ($this->start_date && $d->lt($this->start_date->copy()->startOfDay())) {
            return false;
        }
        if ($this->end_date && $d->gt($this->end_date->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    public function dailyAmountFor(Carbon|string $date): float
    {
        if (!$this->isActiveOn($date)) {
            return 0.0;
        }
        $ref = $date instanceof Carbon ? $date : Carbon::parse($date);
        $days = max(1, $ref->daysInMonth);

        return (float) $this->amount_monthly / $days;
    }
}
