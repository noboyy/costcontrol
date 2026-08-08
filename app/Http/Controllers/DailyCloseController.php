<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\DailyControlService;
use Illuminate\Http\Request;

class DailyCloseController extends Controller
{
    public function __construct(private DailyControlService $daily)
    {
    }

    public function store(Request $request, $projectId)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $projectId)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if (!$project->isUmkm()) {
            return back()->with('error', 'Tutup kas harian hanya untuk unit UMKM.');
        }

        if ($project->isArchived()) {
            return back()->with('error', 'Unit diarsipkan.');
        }

        $request->validate([
            'tanggal' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->daily->closeDay(
            $project,
            $request->tanggal,
            $user->id_akun ?? null,
            $request->notes
        );

        return back()->with('success', 'Kas harian ' . \Carbon\Carbon::parse($request->tanggal)->format('d M Y') . ' ditutup.');
    }

    public function destroy(Request $request, $projectId)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $projectId)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $request->validate([
            'tanggal' => 'required|date',
        ]);

        $this->daily->reopenDay($project, $request->tanggal);

        return back()->with('success', 'Tutup kas dibuka ulang. Entri bisa diedit lagi.');
    }
}
