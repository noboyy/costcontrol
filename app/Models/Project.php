<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

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
        'opening_balance',
        'status',
        'cogs_ratio_alert',
        'lock_closed_days',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'project_value' => 'decimal:2',
        'opening_balance' => 'decimal:2',
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

    public function galleries()
    {
        return $this->hasMany(ProjectGallery::class, 'id_project', 'id_project');
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
        return 'Keberangkatan';
    }

    public function getUnitLabelAttribute(): string
    {
        return 'Keberangkatan';
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
