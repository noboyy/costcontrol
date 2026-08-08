<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\Project;
use App\Services\DailyControlService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $activeProjectIds = Project::where('status', 'active')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->pluck('id_project')
            ->toArray();

        $totalCost = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->sum('total');

        $totalIncome = IncomeEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->sum('total');

        $countCost = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->count();

        $countIncome = IncomeEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->count();

        $startThisMonth = now()->startOfMonth()->format('Y-m-d');
        $startNextMonth = now()->addMonth()->startOfMonth()->format('Y-m-d');
        $startLastMonth = now()->subMonth()->startOfMonth()->format('Y-m-d');

        $thisMonthCost = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->where('tanggal', '>=', $startThisMonth)->where('tanggal', '<', $startNextMonth)->sum('total');

        $lastMonthCost = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->where('tanggal', '>=', $startLastMonth)->where('tanggal', '<', $startThisMonth)->sum('total');

        $thisMonthIncome = IncomeEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->where('tanggal', '>=', $startThisMonth)->where('tanggal', '<', $startNextMonth)->sum('total');

        $lastMonthIncome = IncomeEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->where('tanggal', '>=', $startLastMonth)->where('tanggal', '<', $startThisMonth)->sum('total');

        $costTrend = $this->trend($thisMonthCost, $lastMonthCost);
        $incomeTrend = $this->trend($thisMonthIncome, $lastMonthIncome);

        $recentCosts = CostEntry::with(['costType', 'project'])
            ->whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->orderBy('tanggal', 'desc')->orderBy('id_cost', 'desc')->limit(5)->get()
            ->map(fn ($e) => [
                'id' => $e->id_cost,
                'tanggal' => $e->tanggal,
                'keterangan' => $e->keterangan,
                'total' => $e->total,
                'jenis' => 'biaya',
                'tipe' => $e->costType?->nama,
                'project_id' => $e->id_project,
            ]);

        $recentIncomes = IncomeEntry::with(['incomeType', 'project'])
            ->whereIn('id_project', $activeProjectIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->orderBy('tanggal', 'desc')->orderBy('id_income', 'desc')->limit(5)->get()
            ->map(fn ($e) => [
                'id' => $e->id_income,
                'tanggal' => $e->tanggal,
                'keterangan' => $e->keterangan,
                'total' => $e->total,
                'jenis' => 'pendapatan',
                'tipe' => $e->incomeType?->nama,
                'project_id' => $e->id_project,
            ]);

        $recentActivities = $recentCosts->merge($recentIncomes)
            ->sortByDesc('tanggal')->sortByDesc('id')->take(5)->values();

        $projects = Project::where('status', 'active')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })->orderBy('created_at', 'desc')->get();

        $today = now()->format('Y-m-d');
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

        $weeklyCosts = $this->getWeeklySeries($companyId, $activeProjectIds, CostEntry::class, 'total');

        return response()->json([
            'totalCost' => $totalCost,
            'totalIncome' => $totalIncome,
            'countCost' => $countCost,
            'countIncome' => $countIncome,
            'thisMonthCost' => $thisMonthCost,
            'lastMonthCost' => $lastMonthCost,
            'thisMonthIncome' => $thisMonthIncome,
            'lastMonthIncome' => $lastMonthIncome,
            'costTrend' => $costTrend,
            'incomeTrend' => $incomeTrend,
            'weeklyCost' => $weeklyCosts,
            'activeProjects' => count($activeProjectIds),
            'recentActivities' => $recentActivities,
            'umkmToday' => $umkmToday,
            'umkmTodayTotals' => $umkmTodayTotals,
            'countProject' => $projects->where(fn ($p) => !$p->isUmkm())->count(),
            'countUmkm' => $projects->where(fn ($p) => $p->isUmkm())->count(),
        ]);
    }

    private function trend(float $current, float $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    private function getWeeklySeries(?int $companyId, array $activeIds, string $model, string $column): array
    {
        $series = [];
        $fromDate = now()->subDays(6)->format('Y-m-d');

        $byDate = $model::whereIn('id_project', $activeIds)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            })
            ->where('tanggal', '>=', $fromDate)
            ->selectRaw('DATE(tanggal) as tgl, SUM(' . $column . ') as total')
            ->groupBy('tgl')
            ->orderBy('tgl')
            ->pluck('total', 'tgl')
            ->toArray();

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $series[] = [
                'date' => $day,
                'label' => now()->subDays($i)->format('d M'),
                'value' => (float) ($byDate[$day] ?? 0),
            ];
        }

        return $series;
    }
}