<?php

namespace App\Http\Controllers;

use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\Perusahaan;
use App\Models\Project;
use App\Services\CashService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Investor redirect langsung ke gallery proyeknya
        if ($user->isInvestor()) {
            $investorProject = $user->investorProject()->with('project')->first();
            if ($investorProject && $investorProject->project) {
                $project = $investorProject->project;
                return redirect()->route('cost-centers.gallery', $project->id_project);
            }
            // Fallback kalau tidak ada proyek
            abort(403, 'Tidak ada proyek yang terhubung ke akun investor ini.');
        }

        $companyId = $user->id_perusahaan;

        // Get active project IDs
        $activeProjectIds = Project::where('status', 'active')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('id_perusahaan', $companyId);
            });

        Perusahaan::filterByModule($activeProjectIds, $user->companyModule());

        $activeProjectIds = $activeProjectIds->pluck('id_project')->toArray();

        $startThisMonth = now()->startOfMonth()->format('Y-m-d');
        $startNextMonth = now()->addMonth()->startOfMonth()->format('Y-m-d');
        $startLastMonth = now()->subMonth()->startOfMonth()->format('Y-m-d');

        $costAggregates = CostEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->selectRaw('
                SUM(total) as total_cost,
                SUM(CASE WHEN tanggal >= ? AND tanggal < ? THEN total ELSE 0 END) as this_month_cost,
                SUM(CASE WHEN tanggal >= ? AND tanggal < ? THEN total ELSE 0 END) as last_month_cost,
                COUNT(*) as count_cost
            ', [$startThisMonth, $startNextMonth, $startLastMonth, $startThisMonth])
            ->first();

        $incomeAggregates = IncomeEntry::whereIn('id_project', $activeProjectIds)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->selectRaw('
                SUM(total) as total_income,
                COUNT(*) as count_income
            ')
            ->first();

        $totalCost = (float) ($costAggregates->total_cost ?? 0);
        $totalIncome = (float) ($incomeAggregates->total_income ?? 0);
        $countCost = (int) ($costAggregates->count_cost ?? 0);
        $countIncome = (int) ($incomeAggregates->count_income ?? 0);
        $thisMonthCost = (float) ($costAggregates->this_month_cost ?? 0);
        $lastMonthCost = (float) ($costAggregates->last_month_cost ?? 0);

        $pct = 0.0;
        if ($lastMonthCost > 0) {
            $pct = (($thisMonthCost - $lastMonthCost) / $lastMonthCost) * 100;
        } elseif ($thisMonthCost > 0) {
            $pct = 100.0;
        }
        $pctRounded = (int) round($pct, 0);
        $summaryChangeLabel = $pctRounded === 0 ? '0%' : (($pctRounded > 0 ? '+' : '').$pctRounded.'%');

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
            });

        Perusahaan::filterByModule($projects, $user->companyModule());

        $projects = $projects->orderBy('created_at', 'desc')->get();

        $today = now()->format('Y-m-d');

        $projectSummaries = $projects->take(8)->map(function ($project) {
            return [
                'id_project' => $project->id_project,
                'nama_project' => $project->nama_project,
                'client' => $project->client,
                'mode' => 'project',
                'total_cost' => $project->total_cost,
                'total_income' => $project->total_income,
                'margin' => $project->margin,
            ];
        })->values();

        // Weekly cost series
        $weeklyCosts = $this->getWeeklyCostSeries($companyId, $activeProjectIds);

        $margin = $totalIncome - $totalCost;

        $cashSvc = app(CashService::class);
        $companyPosition = $cashSvc->positionCompany($companyId);
        $companySummary = $cashSvc->summaryCompany($companyId);

        return view('dashboard', [
            'title' => 'Dashboard',
            'summaryTotal' => 'Rp '.number_format($totalCost, 0, ',', '.'),
            'summaryBudget' => 'Rp '.number_format($totalIncome, 0, ',', '.'),
            'summaryChangeLabel' => $summaryChangeLabel,
            'summaryUsed' => 'Rp '.number_format($totalCost, 0, ',', '.'),
            'summaryRemaining' => 'Rp '.number_format($margin, 0, ',', '.'),
            'summaryTxCount' => (string) ($countCost + $countIncome),
            'recentActivities' => $recentActivities,
            'projectSummaries' => $projectSummaries,
            'weeklyCosts' => $weeklyCosts,
            'countProject' => $projects->count(),
            'companyPosition' => $companyPosition,
            'companySummary' => $companySummary,
            'module' => $user->companyModule(),
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
