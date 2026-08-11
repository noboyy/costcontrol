<?php

namespace App\Services;

use App\Models\Project;
use Carbon\Carbon;

class CashService
{
    /**
     * Posisi kas berjalan per tanggal:
     * saldo_awal + pemasukan - pengeluaran (s/d tanggal tsb).
     */
    public function position(Project $project, Carbon|string|null $date = null): array
    {
        $d = $date instanceof Carbon ? $date->copy() : Carbon::parse($date ?: now());
        $day = $d->format('Y-m-d');

        $opening = (float) ($project->opening_balance ?? 0);
        $income = (float) $project->incomeEntries()->whereDate('tanggal', '<=', $day)->sum('total');
        $cost = (float) $project->costEntries()->whereDate('tanggal', '<=', $day)->sum('total');

        $balance = $opening + $income - $cost;

        return [
            'date' => $day,
            'opening' => $opening,
            'income_to_date' => $income,
            'cost_to_date' => $cost,
            'balance' => $balance,
            'is_negative' => $balance < 0,
        ];
    }

    /**
     * Series harian untuk chart kas berjalan (in, out, saldo).
     */
    public function series(Project $project, Carbon|string $from, Carbon|string $to): array
    {
        $start = $from instanceof Carbon ? $from->copy()->startOfDay() : Carbon::parse($from)->startOfDay();
        $end = $to instanceof Carbon ? $to->copy()->startOfDay() : Carbon::parse($to)->startOfDay();

        $incomeByDay = $project->incomeEntries()
            ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->selectRaw('DATE(tanggal) as tgl, SUM(total) as total')
            ->groupBy('tgl')
            ->pluck('total', 'tgl');

        $costByDay = $project->costEntries()
            ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->selectRaw('DATE(tanggal) as tgl, SUM(total) as total')
            ->groupBy('tgl')
            ->pluck('total', 'tgl');

        // Saldo awal sebelum periode dari posisi sehari sebelum from
        $opening = $this->position($project, $start->copy()->subDay())['balance'];

        $rows = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $day = $cursor->format('Y-m-d');
            $in = (float) ($incomeByDay[$day] ?? 0);
            $out = (float) ($costByDay[$day] ?? 0);
            $opening += $in - $out;

            $rows[] = [
                'date' => $day,
                'label' => $cursor->format('d M'),
                'in' => $in,
                'out' => $out,
                'balance' => $opening,
            ];

            $cursor->addDay();
        }

        return $rows;
    }

    /**
     * Forecast berbasis run-rate 7 hari terakhir:
     * rata-rata pengeluaran harian, estimasi hari sampai budget habis.
     */
    public function forecast(Project $project, int $windowDays = 7): array
    {
        $windowDays = max(1, $windowDays);
        $from = now()->subDays($windowDays - 1);

        $cost = (float) $project->costEntries()
            ->where('tanggal', '>=', $from->format('Y-m-d'))
            ->sum('total');

        $income = (float) $project->incomeEntries()
            ->where('tanggal', '>=', $from->format('Y-m-d'))
            ->sum('total');

        $avgDailyCost = $cost / $windowDays;
        $avgDailyIncome = $income / $windowDays;
        $netBurn = $avgDailyCost - $avgDailyIncome;

        $pos = $this->position($project);
        $balance = $pos['balance'];

        $budget = match ($project->budget_period ?: Project::BUDGET_TOTAL) {
            Project::BUDGET_DAILY => $project->daily_budget !== null ? (float) $project->daily_budget * now()->daysInMonth : null,
            Project::BUDGET_MONTHLY => $project->monthly_budget !== null ? (float) $project->monthly_budget : null,
            default => $project->project_value !== null ? (float) $project->project_value : null,
        };

        $daysToDeplete = null;
        if ($netBurn > 0 && $budget !== null && $budget > 0) {
            $remaining = $budget - $pos['cost_to_date'];
            $daysToDeplete = $remaining > 0 ? (int) floor($remaining / $netBurn) : 0;
        }

        $projectedEndCost = $avgDailyCost > 0 && $project->date_end && $project->date_end->isFuture()
            ? $pos['cost_to_date'] + ($avgDailyCost * now()->diffInDays($project->date_end, false))
            : null;

        return [
            'window_days' => $windowDays,
            'avg_daily_cost' => round($avgDailyCost, 2),
            'avg_daily_income' => round($avgDailyIncome, 2),
            'net_burn_daily' => round($netBurn, 2),
            'current_balance' => $balance,
            'budget_target' => $budget,
            'days_to_deplete' => $daysToDeplete,
            'projected_end_cost' => $projectedEndCost !== null ? round($projectedEndCost, 2) : null,
            'over_projected' => $projectedEndCost !== null && $budget !== null && $projectedEndCost > $budget,
        ];
    }
}
