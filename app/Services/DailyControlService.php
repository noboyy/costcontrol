<?php

namespace App\Services;

use App\Models\CostEntry;
use App\Models\DailyClose;
use App\Models\FixedCost;
use App\Models\IncomeEntry;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DailyControlService
{
    /** Categories treated as variable COGS / bahan baku */
    public const COGS_CATEGORIES = ['bahan_baku', 'material'];

    /** Controllable daily ops */
    public const OPS_CATEGORIES = ['ops_harian', 'overhead'];

    /**
     * Build daily snapshot for a unit (cash + pro-rate fixed).
     */
    public function snapshot(Project $project, Carbon|string $date): array
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $day = $d->format('Y-m-d');

        $costEntries = CostEntry::with('costType')
            ->where('id_project', $project->id_project)
            ->whereDate('tanggal', $day)
            ->get();

        $income = (float) IncomeEntry::where('id_project', $project->id_project)
            ->whereDate('tanggal', $day)
            ->sum('total');

        $totalCash = (float) $costEntries->sum('total');
        $totalCogs = (float) $costEntries->filter(fn ($e) => $this->isCogs($e))->sum('total');
        $totalOps = (float) $costEntries->filter(fn ($e) => $this->isOps($e))->sum('total');
        $otherCash = $totalCash - $totalCogs - $totalOps;

        $fixedItems = FixedCost::where('id_project', $project->id_project)
            ->where('is_active', true)
            ->get()
            ->filter(fn (FixedCost $f) => $f->isActiveOn($d));

        $fixedProrate = (float) $fixedItems->sum(fn (FixedCost $f) => $f->dailyAmountFor($d));
        $fixedBreakdown = $fixedItems->map(fn (FixedCost $f) => [
            'id' => $f->id_fixed_cost,
            'nama' => $f->nama,
            'amount_monthly' => (float) $f->amount_monthly,
            'amount_daily' => $f->dailyAmountFor($d),
        ])->values()->all();

        $economic = $totalCash + $fixedProrate;
        $marginCash = $income - $totalCash;
        $marginEconomic = $income - $economic;
        $cogsRatio = $income > 0 ? $totalCogs / $income : null;

        $dailyBudget = $project->budgetTargetForDate($d);
        $overBudget = $dailyBudget !== null && $totalCash > $dailyBudget;

        $threshold = $project->cogs_ratio_alert !== null
            ? (float) $project->cogs_ratio_alert
            : 0.45;

        $alerts = $this->buildAlerts(
            income: $income,
            totalCash: $totalCash,
            totalCogs: $totalCogs,
            cogsRatio: $cogsRatio,
            threshold: $threshold,
            overBudget: $overBudget,
            dailyBudget: $dailyBudget,
            marginCash: $marginCash,
        );

        $closed = DailyClose::where('id_project', $project->id_project)
            ->whereDate('tanggal', $day)
            ->first();

        return [
            'date' => $day,
            'income' => $income,
            'cost_cash' => $totalCash,
            'cogs' => $totalCogs,
            'ops' => $totalOps,
            'other_cash' => max(0, $otherCash),
            'fixed_prorate' => $fixedProrate,
            'fixed_breakdown' => $fixedBreakdown,
            'cost_economic' => $economic,
            'margin_cash' => $marginCash,
            'margin_economic' => $marginEconomic,
            'cogs_ratio' => $cogsRatio,
            'cogs_ratio_pct' => $cogsRatio !== null ? $cogsRatio * 100 : null,
            'cogs_threshold' => $threshold,
            'daily_budget' => $dailyBudget,
            'over_budget' => $overBudget,
            'budget_usage_pct' => $project->budgetUsagePercent($totalCash, $dailyBudget),
            'alerts' => $alerts,
            'leak_alert' => collect($alerts)->contains(fn ($a) => ($a['type'] ?? '') === 'leak'),
            'is_closed' => (bool) $closed,
            'close' => $closed,
            'entry_count' => $costEntries->count(),
        ];
    }

    public function closeDay(Project $project, Carbon|string $date, ?int $closedBy = null, ?string $notes = null): DailyClose
    {
        $snap = $this->snapshot($project, $date);
        $day = $snap['date'];

        return DailyClose::updateOrCreate(
            [
                'id_project' => $project->id_project,
                'tanggal' => $day,
            ],
            [
                'id_perusahaan' => $project->id_perusahaan,
                'total_income' => $snap['income'],
                'total_cost_cash' => $snap['cost_cash'],
                'total_cogs' => $snap['cogs'],
                'total_ops' => $snap['ops'],
                'total_fixed_prorate' => $snap['fixed_prorate'],
                'total_cost_economic' => $snap['cost_economic'],
                'margin_cash' => $snap['margin_cash'],
                'margin_economic' => $snap['margin_economic'],
                'cogs_ratio' => $snap['cogs_ratio'],
                'daily_budget' => $snap['daily_budget'],
                'over_budget' => $snap['over_budget'],
                'leak_alert' => $snap['leak_alert'],
                'notes' => $notes,
                'closed_by' => $closedBy,
                'closed_at' => now(),
            ]
        );
    }

    public function reopenDay(Project $project, Carbon|string $date): bool
    {
        $d = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

        return (bool) DailyClose::where('id_project', $project->id_project)
            ->whereDate('tanggal', $d)
            ->delete();
    }

    public function isDayClosed(Project $project, Carbon|string $date): bool
    {
        $d = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

        return DailyClose::where('id_project', $project->id_project)
            ->whereDate('tanggal', $d)
            ->exists();
    }

    /**
     * Recent daily snapshots for chart / history (last N days).
     */
    public function recentDays(Project $project, int $days = 7): Collection
    {
        $rows = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $snap = $this->snapshot($project, $d);
            $rows->push($snap);
        }

        return $rows;
    }

    private function isCogs(CostEntry $entry): bool
    {
        $cat = strtolower((string) ($entry->costType?->kategori ?? ''));

        return in_array($cat, self::COGS_CATEGORIES, true);
    }

    private function isOps(CostEntry $entry): bool
    {
        $cat = strtolower((string) ($entry->costType?->kategori ?? ''));

        return in_array($cat, self::OPS_CATEGORIES, true);
    }

    private function buildAlerts(
        float $income,
        float $totalCash,
        float $totalCogs,
        ?float $cogsRatio,
        float $threshold,
        bool $overBudget,
        ?float $dailyBudget,
        float $marginCash,
    ): array {
        $alerts = [];

        if ($overBudget && $dailyBudget !== null) {
            $alerts[] = [
                'type' => 'budget',
                'level' => 'danger',
                'title' => 'Over pagu harian',
                'message' => 'Biaya kas melebihi pagu Rp '.number_format($dailyBudget, 0, ',', '.').'.',
            ];
        }

        if ($income <= 0 && $totalCash > 0) {
            $alerts[] = [
                'type' => 'leak',
                'level' => 'warning',
                'title' => 'Biaya tanpa omzet',
                'message' => 'Ada pengeluaran kas tanpa pendapatan hari ini. Cek stok / waste / pencatatan omzet.',
            ];
        }

        if ($cogsRatio !== null && $cogsRatio > $threshold && $income > 0) {
            $alerts[] = [
                'type' => 'leak',
                'level' => 'danger',
                'title' => 'COGS tinggi',
                'message' => 'Rasio bahan baku '.number_format($cogsRatio * 100, 1).'% dari omzet (batas '.number_format($threshold * 100, 0).'%). Cek waste/shrinkage.',
            ];
        }

        if ($marginCash < 0 && $income > 0) {
            $alerts[] = [
                'type' => 'margin',
                'level' => 'warning',
                'title' => 'Profit kas negatif',
                'message' => 'Omzet belum menutup biaya kas hari ini. Batasi ops non-esensial besok.',
            ];
        }

        return $alerts;
    }
}
