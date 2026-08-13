<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Akun;
use App\Models\CostEntry;
use App\Models\CostType;
use App\Models\IncomeEntry;
use App\Models\IncomeType;
use App\Models\ProjectInvestor;
use App\Services\DailyControlService;
use App\Services\CashService;
use Illuminate\Http\Request;

class InvestorController extends Controller
{
    /**
     * Ambil project yang di-assign ke investor yang login.
     */
    public function project(Request $request)
    {
        $user = $request->user();

        $relation = ProjectInvestor::where('id_akun', $user->id_akun)
            ->with('project')
            ->first();

        if (! $relation || ! $relation->project) {
            return response()->json(['message' => 'Tidak ada proyek yang di-assign.'], 404);
        }

        $project = $relation->project;
        $today = now()->toDateString();

        $todayCost = $project->costOnDate($today);
        $todayIncome = $project->incomeOnDate($today);
        $monthCost = $project->costInMonth();
        $monthIncome = $project->incomeInMonth();

        $dailySnap = null;
        $recentDays = collect();
        $fixedCosts = collect();
        if ($project->isUmkm()) {
            $daily = app(DailyControlService::class);
            $dailySnap = $daily->snapshot($project, $today);
            $recentDays = $daily->recentDays($project, 7);
            $fixedCosts = $project->fixedCosts()->orderBy('nama')->get();
            $todayCost = $dailySnap['cost_cash'];
            $todayIncome = $dailySnap['income'];
        }

        $cash = app(CashService::class);
        $totalCost = (float) $project->costEntries()->sum('total');
        $totalIncome = (float) $project->incomeEntries()->sum('total');

        $costTypeMap = CostType::where('id_perusahaan', $project->id_perusahaan ?: null)->pluck('kategori', 'id_cost_type');
        $incomeTypeMap = IncomeType::where('id_perusahaan', $project->id_perusahaan ?: null)->pluck('kategori', 'id_income_type');

        $byCostCategory = $project->costEntries()
            ->get()
            ->groupBy(fn ($c) => $costTypeMap[$c->id_cost_type] ?? 'other')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        $byIncomeCategory = $project->incomeEntries()
            ->get()
            ->groupBy(fn ($i) => $incomeTypeMap[$i->id_income_type] ?? 'other')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        return response()->json([
            'project' => [
                'id_project' => $project->id_project,
                'nama_project' => $project->nama_project,
                'client' => $project->client,
                'lokasi' => $project->lokasi,
                'date_start' => $project->date_start?->format('Y-m-d'),
                'date_end' => $project->date_end?->format('Y-m-d'),
                'status' => $project->status,
                'mode' => $project->mode,
                'project_value' => $project->project_value !== null ? (float) $project->project_value : null,
            ],
            'summaries' => [
                'totalCost' => $totalCost,
                'totalIncome' => $totalIncome,
                'margin' => $totalIncome - $totalCost,
                'todayCost' => $todayCost,
                'todayIncome' => $todayIncome,
                'todayMargin' => $todayIncome - $todayCost,
                'monthCost' => $monthCost,
                'monthIncome' => $monthIncome,
            ],
            'cashPosition' => $cash->position($project),
            'dailySnap' => $dailySnap,
            'recentDays' => $recentDays,
            'fixedCosts' => $fixedCosts,
            'categories' => [
                'byCost' => $byCostCategory,
                'byIncome' => $byIncomeCategory,
            ],
        ]);
    }

    /**
     * List cost entries proyek investor.
     */
    public function costs(Request $request)
    {
        $user = $request->user();
        $projectId = $this->resolveProjectId($user);

        if (! $projectId) {
            return response()->json(['message' => 'Tidak ada proyek yang di-assign.'], 404);
        }

        $from = $request->get('from');
        $to = $request->get('to');

        $query = CostEntry::with(['costType'])
            ->where('id_project', $projectId)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($from) {
            $query->where('tanggal', '>=', $from);
        }
        if ($to) {
            $query->where('tanggal', '<=', $to);
        }

        $entries = $query->get()->map(fn ($e) => [
            'id' => $e->id_cost,
            'tanggal' => $e->tanggal?->format('Y-m-d'),
            'keterangan' => $e->keterangan,
            'qty' => (float) $e->qty,
            'unit' => $e->unit,
            'harga_satuan' => (float) $e->harga_satuan,
            'total' => (float) $e->total,
            'catatan' => $e->catatan,
            'tipe' => $e->costType?->nama,
            'kategori' => $e->costType?->kategori,
            'file_bukti' => $e->file_bukti,
        ]);

        return response()->json(['costs' => $entries]);
    }

    /**
     * List income entries proyek investor.
     */
    public function incomes(Request $request)
    {
        $user = $request->user();
        $projectId = $this->resolveProjectId($user);

        if (! $projectId) {
            return response()->json(['message' => 'Tidak ada proyek yang di-assign.'], 404);
        }

        $from = $request->get('from');
        $to = $request->get('to');

        $query = IncomeEntry::with(['incomeType'])
            ->where('id_project', $projectId)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        if ($from) {
            $query->where('tanggal', '>=', $from);
        }
        if ($to) {
            $query->where('tanggal', '<=', $to);
        }

        $entries = $query->get()->map(fn ($e) => [
            'id' => $e->id_income,
            'tanggal' => $e->tanggal?->format('Y-m-d'),
            'keterangan' => $e->keterangan,
            'qty' => (float) $e->qty,
            'unit' => $e->unit,
            'harga_satuan' => (float) $e->harga_satuan,
            'total' => (float) $e->total,
            'catatan' => $e->catatan,
            'tipe' => $e->incomeType?->nama,
            'kategori' => $e->incomeType?->kategori,
            'file_bukti' => $e->file_bukti,
        ]);

        return response()->json(['incomes' => $entries]);
    }

    /**
     * Ringkasan laporan per periode.
     */
    public function report(Request $request)
    {
        $user = $request->user();
        $projectId = $this->resolveProjectId($user);

        if (! $projectId) {
            return response()->json(['message' => 'Tidak ada proyek yang di-assign.'], 404);
        }

        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        $costs = CostEntry::with('costType')
            ->where('id_project', $projectId)
            ->whereBetween('tanggal', [$from, $to])
            ->get();

        $incomes = IncomeEntry::with('incomeType')
            ->where('id_project', $projectId)
            ->whereBetween('tanggal', [$from, $to])
            ->get();

        $totalCost = (float) $costs->sum('total');
        $totalIncome = (float) $incomes->sum('total');

        $byCostCategory = $costs
            ->groupBy(fn ($c) => $c->costType?->kategori ?: 'other')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        $byIncomeCategory = $incomes
            ->groupBy(fn ($i) => $i->incomeType?->kategori ?: 'other')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'totalCost' => $totalCost,
            'totalIncome' => $totalIncome,
            'margin' => $totalIncome - $totalCost,
            'byCostCategory' => $byCostCategory,
            'byIncomeCategory' => $byIncomeCategory,
        ]);
    }

    private function resolveProjectId(Akun $user): ?int
    {
        $relation = ProjectInvestor::where('id_akun', $user->id_akun)->first();

        return $relation?->id_project;
    }
}
