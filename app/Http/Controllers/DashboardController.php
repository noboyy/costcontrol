<?php

namespace App\Http\Controllers;

use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\Project;
use App\Services\DailyControlService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        // Get active project IDs
        $activeProjectIds = Project::where('status', 'active')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->pluck('id_project')
            ->toArray();

        // KPI Summary
        $totalCost = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->sum('total');

        $totalIncome = IncomeEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->sum('total');

        $countCost = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->count();

        $countIncome = IncomeEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->count();

        // Monthly comparison
        $startThisMonth = now()->startOfMonth()->format('Y-m-d');
        $startNextMonth = now()->addMonth()->startOfMonth()->format('Y-m-d');
        $startLastMonth = now()->subMonth()->startOfMonth()->format('Y-m-d');

        $thisMonthCost = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->where('tanggal', '>=', $startThisMonth)
            ->where('tanggal', '<', $startNextMonth)
            ->sum('total');

        $lastMonthCost = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->where('tanggal', '>=', $startLastMonth)
            ->where('tanggal', '<', $startThisMonth)
            ->sum('total');

        $pct = 0.0;
        if ($lastMonthCost > 0) {
            $pct = (($thisMonthCost - $lastMonthCost) / $lastMonthCost) * 100;
        } elseif ($thisMonthCost > 0) {
            $pct = 100.0;
        }
        $pctRounded = (int) round($pct, 0);
        $summaryChangeLabel = $pctRounded === 0 ? '0%' : (($pctRounded > 0 ? '+' : '') . $pctRounded . '%');

        // Recent activities
        $recentCosts = CostEntry::with(['costType', 'project'])
            ->whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_cost', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id_cost,
                    'tanggal' => $entry->tanggal,
                    'keterangan' => $entry->keterangan,
                    'total' => $entry->total,
                    'jenis' => 'biaya',
                    'tipe' => $entry->costType?->nama,
                    'project_id' => $entry->id_project,
                ];
            });

        $recentIncomes = IncomeEntry::with(['incomeType', 'project'])
            ->whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id_income', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id_income,
                    'tanggal' => $entry->tanggal,
                    'keterangan' => $entry->keterangan,
                    'total' => $entry->total,
                    'jenis' => 'pendapatan',
                    'tipe' => $entry->incomeType?->nama,
                    'project_id' => $entry->id_project,
                ];
            });

        $recentActivities = $recentCosts->merge($recentIncomes)
            ->sortByDesc('tanggal')
            ->sortByDesc('id')
            ->take(5)
            ->values();

        // Unit summaries
        $projects = Project::where('status', 'active')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $today = now()->format('Y-m-d');

        $projectSummaries = $projects->where(fn ($p) => !$p->isUmkm())->take(8)->map(function ($project) {
            return [
                'id_project' => $project->id_project,
                'nama_project' => $project->nama_project,
                'client' => $project->client,
                'mode' => $project->mode ?: Project::MODE_PROJECT,
                'total_cost' => $project->total_cost,
                'total_income' => $project->total_income,
                'margin' => $project->margin,
            ];
        })->values();

        $dailyService = app(DailyControlService::class);
        $umkmToday = $projects->where(fn ($p) => $p->isUmkm())->map(function ($unit) use ($today, $dailyService) {
            $snap = $dailyService->snapshot($unit, $today);

            return [
                'id_project' => $unit->id_project,
                'nama_project' => $unit->nama_project,
                'lokasi' => $unit->lokasi,
                'today_cost' => $snap['cost_cash'],
                'today_income' => $snap['income'],
                'today_margin' => $snap['margin_cash'],
                'margin_economic' => $snap['margin_economic'],
                'fixed_prorate' => $snap['fixed_prorate'],
                'daily_target' => $snap['daily_budget'],
                'usage_pct' => $snap['budget_usage_pct'],
                'leak_alert' => $snap['leak_alert'],
                'over_budget' => $snap['over_budget'],
                'is_closed' => $snap['is_closed'],
                'cogs_ratio_pct' => $snap['cogs_ratio_pct'],
            ];
        })->values();

        $umkmTodayTotals = [
            'cost' => $umkmToday->sum('today_cost'),
            'income' => $umkmToday->sum('today_income'),
            'margin' => $umkmToday->sum('today_margin'),
            'margin_economic' => $umkmToday->sum('margin_economic'),
            'alerts' => $umkmToday->filter(fn ($u) => $u['leak_alert'] || $u['over_budget'])->count(),
            'count' => $umkmToday->count(),
        ];

        // Weekly cost series
        $weeklyCosts = $this->getWeeklyCostSeries($companyId, $activeProjectIds);

        $margin = $totalIncome - $totalCost;

        return view('dashboard', [
            'title' => 'Dashboard',
            'summaryTotal' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
            'summaryBudget' => 'Rp ' . number_format($totalIncome, 0, ',', '.'),
            'summaryChangeLabel' => $summaryChangeLabel,
            'summaryUsed' => 'Rp ' . number_format($totalCost, 0, ',', '.'),
            'summaryRemaining' => 'Rp ' . number_format($margin, 0, ',', '.'),
            'summaryTxCount' => (string) ($countCost + $countIncome),
            'recentActivities' => $recentActivities,
            'projectSummaries' => $projectSummaries,
            'umkmToday' => $umkmToday,
            'umkmTodayTotals' => $umkmTodayTotals,
            'weeklyCosts' => $weeklyCosts,
            'countProject' => $projects->where(fn ($p) => !$p->isUmkm())->count(),
            'countUmkm' => $projects->where(fn ($p) => $p->isUmkm())->count(),
        ]);
    }

    private function getWeeklyCostSeries(?int $companyId, array $activeIds): array
    {
        $series = [];
        $fromDate = now()->subDays(6)->format('Y-m-d');

        $costByDate = CostEntry::whereIn('id_project', $activeIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->where('tanggal', '>=', $fromDate)
            ->selectRaw('DATE(tanggal) as tgl, SUM(total) as total_cost')
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->pluck('total_cost', 'tgl')
            ->toArray();

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $series[] = [
                'date' => $day,
                'label' => now()->subDays($i)->format('d M'),
                'value' => $costByDate[$day] ?? 0,
            ];
        }

        return $series;
    }
}
