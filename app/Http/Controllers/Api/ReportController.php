<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\Project;
use App\Services\DailyControlService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $projectId = $request->get('project_id');
        $mode = $request->get('mode');

        $units = Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->where('status', 'active')
            ->orderBy('nama_project')
            ->get();

        $queryProjects = Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->when($projectId, fn ($q) => $q->where('id_project', $projectId))
            ->when($mode === 'umkm', fn ($q) => $q->where('mode', 'umkm'))
            ->when($mode === 'project', fn ($q) => $q->where(fn ($qq) => $qq->where('mode', 'project')->orWhereNull('mode')));

        $selected = $queryProjects->get();
        $ids = $selected->pluck('id_project')->all();

        $costs = CostEntry::with(['costType', 'project'])
            ->whereIn('id_project', $ids ?: [0])
            ->whereBetween('tanggal', [$from, $to])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $incomes = IncomeEntry::with(['incomeType', 'project'])
            ->whereIn('id_project', $ids ?: [0])
            ->whereBetween('tanggal', [$from, $to])
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalCost = (float) $costs->sum('total');
        $totalIncome = (float) $incomes->sum('total');
        $margin = $totalIncome - $totalCost;

        $byCostCategory = $costs->groupBy(fn ($c) => $c->costType?->kategori ?: 'other')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        $byIncomeCategory = $incomes->groupBy(fn ($i) => $i->incomeType?->kategori ?: 'other')
            ->map(fn ($g) => (float) $g->sum('total'))
            ->sortDesc();

        $byUnit = $selected->map(function ($p) use ($from, $to) {
            $c = (float) $p->costEntries()->whereBetween('tanggal', [$from, $to])->sum('total');
            $i = (float) $p->incomeEntries()->whereBetween('tanggal', [$from, $to])->sum('total');

            return [
                'id' => $p->id_project,
                'nama' => $p->nama_project,
                'mode' => $p->mode_label,
                'cost' => $c,
                'income' => $i,
                'margin' => $i - $c,
            ];
        })->sortByDesc('income')->values();

        $dailyRows = [];
        if ($projectId) {
            $unit = $selected->first();
            if ($unit && $unit->isUmkm()) {
                $svc = app(DailyControlService::class);
                $start = Carbon::parse($from);
                $end = Carbon::parse($to);
                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    $dailyRows[] = $svc->snapshot($unit, $d);
                }
            }
        }

        return response()->json([
            'from' => $from,
            'to' => $to,
            'projectId' => $projectId,
            'mode' => $mode,
            'units' => $units->map(fn ($u) => ['id' => $u->id_project, 'nama' => $u->nama_project, 'mode' => $u->mode]),
            'totalCost' => $totalCost,
            'totalIncome' => $totalIncome,
            'margin' => $margin,
            'byCostCategory' => $byCostCategory->map(fn ($v, $k) => ['kategori' => $k, 'total' => $v])->values(),
            'byIncomeCategory' => $byIncomeCategory->map(fn ($v, $k) => ['kategori' => $k, 'total' => $v])->values(),
            'byUnit' => $byUnit,
            'costs' => $costs->map(fn ($c) => [
                'id' => $c->id_cost,
                'tanggal' => $c->tanggal?->format('Y-m-d'),
                'unit' => $c->project?->nama_project,
                'tipe' => $c->costType?->nama,
                'keterangan' => $c->keterangan,
                'qty' => $c->qty,
                'unit_satuan' => $c->unit,
                'harga_satuan' => (float) $c->harga_satuan,
                'total' => (float) $c->total,
            ]),
            'incomes' => $incomes->map(fn ($i) => [
                'id' => $i->id_income,
                'tanggal' => $i->tanggal?->format('Y-m-d'),
                'unit' => $i->project?->nama_project,
                'tipe' => $i->incomeType?->nama,
                'keterangan' => $i->keterangan,
                'qty' => $i->qty,
                'unit_satuan' => $i->unit,
                'harga_satuan' => (float) $i->harga_satuan,
                'total' => (float) $i->total,
            ]),
            'dailyRows' => $dailyRows,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        $projectId = $request->get('project_id');
        $type = $request->get('type', 'all');

        $ids = Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->when($projectId, fn ($q) => $q->where('id_project', $projectId))
            ->pluck('id_project')
            ->all();

        $filename = 'laporan_'.$from.'_'.$to.'.csv';

        return response()->streamDownload(function () use ($ids, $from, $to, $type) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['jenis', 'tanggal', 'unit', 'tipe', 'keterangan', 'qty', 'satuan', 'harga_satuan', 'total']);

            if ($type === 'all' || $type === 'cost') {
                CostEntry::with(['costType', 'project'])
                    ->whereIn('id_project', $ids ?: [0])
                    ->whereBetween('tanggal', [$from, $to])
                    ->orderBy('tanggal', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->chunk(200, function ($rows) use ($out) {
                        foreach ($rows as $r) {
                            fputcsv($out, [
                                'biaya',
                                $r->tanggal?->format('Y-m-d'),
                                $r->project?->nama_project,
                                $r->costType?->nama,
                                $r->keterangan,
                                $r->qty,
                                $r->unit,
                                $r->harga_satuan,
                                $r->total,
                            ]);
                        }
                    });
            }

            if ($type === 'all' || $type === 'income') {
                IncomeEntry::with(['incomeType', 'project'])
                    ->whereIn('id_project', $ids ?: [0])
                    ->whereBetween('tanggal', [$from, $to])
                    ->orderBy('tanggal', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->chunk(200, function ($rows) use ($out) {
                        foreach ($rows as $r) {
                            fputcsv($out, [
                                'pendapatan',
                                $r->tanggal?->format('Y-m-d'),
                                $r->project?->nama_project,
                                $r->incomeType?->nama,
                                $r->keterangan,
                                $r->qty,
                                $r->unit,
                                $r->harga_satuan,
                                $r->total,
                            ]);
                        }
                    });
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
