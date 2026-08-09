<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    public const MODE_PROJECT = 'project';

    public const MODE_UMKM = 'umkm';

    public const BUDGET_TOTAL = 'total';

    public const BUDGET_MONTHLY = 'monthly';

    public const BUDGET_DAILY = 'daily';

    protected $table = 'project';

    protected $primaryKey = 'id_project';

    protected $fillable = [
        'id_perusahaan',
        'id_admin',
        'nama_project',
        'client',
        'lokasi',
        'date_start',
        'date_end',
        'project_value',
        'status',
        'mode',
        'budget_period',
        'daily_budget',
        'monthly_budget',
        'business_type',
        'cogs_ratio_alert',
        'lock_closed_days',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'project_value' => 'decimal:2',
        'daily_budget' => 'decimal:2',
        'monthly_budget' => 'decimal:2',
        'cogs_ratio_alert' => 'decimal:4',
        'lock_closed_days' => 'boolean',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function admin()
    {
        return $this->belongsTo(Pengguna::class, 'id_admin', 'id_pengguna');
    }

    public function costEntries()
    {
        return $this->hasMany(CostEntry::class, 'id_project', 'id_project');
    }

    public function incomeEntries()
    {
        return $this->hasMany(IncomeEntry::class, 'id_project', 'id_project');
    }

    public function admins()
    {
        return $this->belongsToMany(Pengguna::class, 'project_admin', 'id_project', 'id_pengguna');
    }

    public function costPlans()
    {
        return $this->hasMany(ProjectCostPlan::class, 'id_project', 'id_project');
    }

    public function incomePlans()
    {
        return $this->hasMany(ProjectIncomePlan::class, 'id_project', 'id_project');
    }

    public function fixedCosts()
    {
        return $this->hasMany(FixedCost::class, 'id_project', 'id_project');
    }

    public function dailyCloses()
    {
        return $this->hasMany(DailyClose::class, 'id_project', 'id_project');
    }

    public function isProjectMode(): bool
    {
        return ($this->mode ?: self::MODE_PROJECT) === self::MODE_PROJECT;
    }

    public function isUmkm(): bool
    {
        return $this->mode === self::MODE_UMKM;
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getModeLabelAttribute(): string
    {
        return $this->isUmkm() ? 'UMKM' : 'Proyek';
    }

    public function getUnitLabelAttribute(): string
    {
        return $this->isUmkm() ? 'Outlet / Unit' : 'Proyek';
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->costEntries()->sum('total');
    }

    public function getTotalIncomeAttribute(): float
    {
        return (float) $this->incomeEntries()->sum('total');
    }

    public function getMarginAttribute(): float
    {
        return $this->total_income - $this->total_cost;
    }

    public function costOnDate(Carbon|string $date): float
    {
        $d = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        return (float) $this->costEntries()->whereDate('tanggal', $d)->sum('total');
    }

    public function incomeOnDate(Carbon|string $date): float
    {
        $d = $date instanceof Carbon ? $date->format('Y-m-d') : $date;

        return (float) $this->incomeEntries()->whereDate('tanggal', $d)->sum('total');
    }

    public function costInMonth(Carbon|string|null $date = null): float
    {
        $ref = $date instanceof Carbon ? $date : Carbon::parse($date ?: now());
        $start = $ref->copy()->startOfMonth()->format('Y-m-d');
        $end = $ref->copy()->endOfMonth()->format('Y-m-d');

        return (float) $this->costEntries()
            ->whereBetween('tanggal', [$start, $end])
            ->sum('total');
    }

    public function incomeInMonth(Carbon|string|null $date = null): float
    {
        $ref = $date instanceof Carbon ? $date : Carbon::parse($date ?: now());
        $start = $ref->copy()->startOfMonth()->format('Y-m-d');
        $end = $ref->copy()->endOfMonth()->format('Y-m-d');

        return (float) $this->incomeEntries()
            ->whereBetween('tanggal', [$start, $end])
            ->sum('total');
    }

    /**
     * Budget target for a given day based on budget_period.
     */
    public function budgetTargetForDate(Carbon|string|null $date = null): ?float
    {
        $period = $this->budget_period ?: self::BUDGET_TOTAL;

        return match ($period) {
            self::BUDGET_DAILY => $this->daily_budget !== null ? (float) $this->daily_budget : null,
            self::BUDGET_MONTHLY => $this->monthly_budget !== null
                ? (float) $this->monthly_budget / max(1, Carbon::parse($date ?: now())->daysInMonth)
                : null,
            default => $this->project_value !== null ? (float) $this->project_value : null,
        };
    }

    public function monthlyBudgetTarget(): ?float
    {
        if ($this->monthly_budget !== null) {
            return (float) $this->monthly_budget;
        }
        if ($this->budget_period === self::BUDGET_DAILY && $this->daily_budget !== null) {
            return (float) $this->daily_budget * now()->daysInMonth;
        }
        if ($this->project_value !== null) {
            return (float) $this->project_value;
        }

        return null;
    }

    public function budgetUsagePercent(float $actual, ?float $target = null): ?float
    {
        $t = $target ?? $this->budgetTargetForDate();
        if ($t === null || $t <= 0) {
            return null;
        }

        return ($actual / $t) * 100;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeMode($query, string $mode)
    {
        return $query->where('mode', $mode);
    }

    public function scopeUmkm($query)
    {
        return $query->where('mode', self::MODE_UMKM);
    }

    public function scopeProjectMode($query)
    {
        return $query->where(function ($q) {
            $q->where('mode', self::MODE_PROJECT)->orWhereNull('mode');
        });
    }
}
