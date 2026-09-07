<?php

namespace App\Http\Controllers;

use App\Models\CostEntry;
use App\Models\IncomeEntry;
use App\Models\Perusahaan;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PajakController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $perusahaan = Perusahaan::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))->first();

        $periode = $request->get('periode', 'bulan'); // bulan | tahun | keberangkatan
        $projectId = $request->get('project_id');

        $from = null;
        $to = null;
        $label = null;

        if ($periode === 'tahun') {
            $year = (int) $request->get('tahun', now()->year);
            $from = Carbon::create($year, 1, 1)->startOfDay();
            $to = Carbon::create($year, 12, 31)->endOfDay();
            $label = 'Tahun '.$year;
        } elseif ($periode === 'keberangkatan') {
            $projectId = $projectId ?: null;
            $label = 'Per Keberangkatan';
        } else {
            $month = $request->get('bulan', now()->format('Y-m'));
            $from = Carbon::parse($month.'-01')->startOfMonth();
            $to = $from->copy()->endOfMonth();
            $label = $from->translatedFormat('F Y');
        }

        $incomeQuery = IncomeEntry::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId));
        $costQuery = CostEntry::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId));

        if ($periode === 'keberangkatan' && $projectId) {
            $incomeQuery->where('id_project', $projectId);
            $costQuery->where('id_project', $projectId);
        } elseif ($periode !== 'keberangkatan') {
            $incomeQuery->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()]);
            $costQuery->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()]);
        }

        $omzet = (float) $incomeQuery->sum('total');
        $costProject = (float) (clone $costQuery)->whereNotNull('id_project')->sum('total');
        $costUmum = (float) (clone $costQuery)->whereNull('id_project')->sum('total');
        $laba = $omzet - $costProject - $costUmum;

        $mode = $perusahaan?->mode_pajak ?? Perusahaan::PAJAK_FINAL_OMZET;
        $pajakAktif = (bool) ($perusahaan?->pajak_aktif ?? true);

        if ($mode === Perusahaan::PAJAK_BADAN_LABA) {
            $tarif = (float) ($perusahaan?->tarif_pph_laba ?? 22.0);
            $dasarPajak = $laba > 0 ? $laba : 0.0;
            $pajak = $dasarPajak * ($tarif / 100);
            $dasarLabel = 'Laba (estimasi)';
        } else {
            $tarif = (float) ($perusahaan?->tarif_pph_omzet ?? 0.5);
            $dasarPajak = $omzet;
            $pajak = $omzet * ($tarif / 100);
            $dasarLabel = 'Omzet';
        }

        // Breakdown per keberangkatan
        $projects = Project::with(['costEntries', 'incomeEntries'])
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->where('status', 'active')
            ->orderBy('nama_project')
            ->get();

        $rows = $projects->map(function ($p) use ($from, $to, $periode, $mode, $tarif, $projectId) {
            $inc = $p->incomeEntries;
            $cost = $p->costEntries;

            if ($periode !== 'keberangkatan' && $from) {
                $inc = $inc->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()]);
                $cost = $cost->whereBetween('tanggal', [$from->toDateString(), $to->toDateString()]);
            }

            $omzetP = (float) $inc->sum('total');
            $costP = (float) $cost->sum('total');
            $labaP = $omzetP - $costP;

            $dasar = $mode === Perusahaan::PAJAK_BADAN_LABA ? max(0, $labaP) : $omzetP;

            return [
                'id_project' => $p->id_project,
                'nama_project' => $p->nama_project,
                'omzet' => $omzetP,
                'cost' => $costP,
                'laba' => $labaP,
                'pajak' => $dasar * ($tarif / 100),
            ];
        })->values();

        $years = collect();
        $yMin = IncomeEntry::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))->min('tanggal');
        $yMax = IncomeEntry::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))->max('tanggal');
        if ($yMin && $yMax) {
            $fromY = (int) Carbon::parse($yMin)->year;
            $toY = (int) Carbon::parse($yMax)->year;
            $years = collect(range($toY, $fromY));
        }

        return view('pajak.index', [
            'title' => 'Perkiraan Pajak',
            'perusahaan' => $perusahaan,
            'periode' => $periode,
            'label' => $label,
            'projectId' => $projectId,
            'projects' => $projects,
            'mode' => $mode,
            'tarif' => $tarif,
            'pajakAktif' => $pajakAktif,
            'dasarLabel' => $dasarLabel,
            'omzet' => $omzet,
            'costProject' => $costProject,
            'costUmum' => $costUmum,
            'laba' => $laba,
            'dasarPajak' => $dasarPajak,
            'pajak' => $pajak,
            'rows' => $rows,
            'years' => $years,
        ]);
    }
}
