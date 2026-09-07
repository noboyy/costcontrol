<?php

namespace App\Http\Controllers;

use App\Models\CostEntry;
use App\Models\CostType;
use App\Models\IncomeEntry;
use App\Models\IncomeType;
use App\Models\Project;
use App\Models\ProjectInvestor;
use App\Services\CashService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvestorController extends Controller
{
    /**
     * Project tunggal milik investor yang login.
     */
    private function resolveProject(): Project
    {
        $user = auth()->user();
        $relation = ProjectInvestor::with('project')
            ->where('id_akun', $user->id_akun ?? $user->id)
            ->first();

        if (! $relation || ! $relation->project) {
            throw new HttpException(403, 'Tidak ada keberangkatan yang terhubung ke akun investor ini.');
        }

        return $relation->project;
    }

    /**
     * Dashboard ringkasan investor.
     */
    public function index()
    {
        $project = $this->resolveProject();
        $today = now()->toDateString();

        $totalCost = (float) $project->costEntries()->sum('total');
        $totalIncome = (float) $project->incomeEntries()->sum('total');

        $cash = app(CashService::class)->position($project);

        $costTypeMap = CostType::where('id_perusahaan', $project->id_perusahaan ?: null)
            ->pluck('kategori', 'id_cost_type');
        $incomeTypeMap = IncomeType::where('id_perusahaan', $project->id_perusahaan ?: null)
            ->pluck('kategori', 'id_income_type');

        $byCost = $project->costEntries()
            ->get()
            ->groupBy(fn ($c) => $costTypeMap[$c->id_cost_type] ?? 'Lainnya')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        $byIncome = $project->incomeEntries()
            ->get()
            ->groupBy(fn ($i) => $incomeTypeMap[$i->id_income_type] ?? 'Lainnya')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        return view('investor.index', [
            'project' => $project,
            'totalCost' => $totalCost,
            'totalIncome' => $totalIncome,
            'margin' => $totalIncome - $totalCost,
            'todayCost' => $project->costOnDate($today),
            'todayIncome' => $project->incomeOnDate($today),
            'monthCost' => $project->costInMonth(),
            'monthIncome' => $project->incomeInMonth(),
            'cash' => $cash,
            'byCost' => $byCost,
            'byIncome' => $byIncome,
            'empty' => $totalCost == 0 && $totalIncome == 0,
        ]);
    }

    /**
     * Daftar biaya (read-only) — dukung filter tanggal.
     */
    public function costs(Request $request)
    {
        $project = $this->resolveProject();

        $costs = CostEntry::with(['costType', 'gallery'])
            ->where('id_project', $project->id_project)
            ->when($request->filled('from'), fn ($q) => $q->where('tanggal', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('tanggal', '<=', $request->input('to')))
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('investor.costs', [
            'project' => $project,
            'entries' => $costs,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'total' => (float) $costs->sum('total'),
        ]);
    }

    /**
     * Daftar pendapatan (read-only) — dukung filter tanggal.
     */
    public function incomes(Request $request)
    {
        $project = $this->resolveProject();

        $incomes = IncomeEntry::with(['incomeType', 'gallery'])
            ->where('id_project', $project->id_project)
            ->when($request->filled('from'), fn ($q) => $q->where('tanggal', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('tanggal', '<=', $request->input('to')))
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('investor.incomes', [
            'project' => $project,
            'entries' => $incomes,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'total' => (float) $incomes->sum('total'),
        ]);
    }

    /**
     * Laporan biaya/pendapatan per kategori dalam periode.
     */
    public function report(Request $request)
    {
        $project = $this->resolveProject();

        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $costs = CostEntry::with('costType')
            ->where('id_project', $project->id_project)
            ->whereBetween('tanggal', [$from, $to])
            ->get();

        $incomes = IncomeEntry::with('incomeType')
            ->where('id_project', $project->id_project)
            ->whereBetween('tanggal', [$from, $to])
            ->get();

        $totalCost = (float) $costs->sum('total');
        $totalIncome = (float) $incomes->sum('total');

        $byCost = $costs
            ->groupBy(fn ($c) => $c->costType?->kategori ?: 'Lainnya')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        $byIncome = $incomes
            ->groupBy(fn ($i) => $i->incomeType?->kategori ?: 'Lainnya')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        return view('investor.report', [
            'project' => $project,
            'from' => $from,
            'to' => $to,
            'totalCost' => $totalCost,
            'totalIncome' => $totalIncome,
            'margin' => $totalIncome - $totalCost,
            'byCost' => $byCost,
            'byIncome' => $byIncome,
        ]);
    }
}
