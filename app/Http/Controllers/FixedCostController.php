<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDecimal;
use App\Models\FixedCost;
use App\Models\Project;
use Illuminate\Http\Request;

class FixedCostController extends Controller
{
    use HandlesDecimal;

    public function store(Request $request, $projectId)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $projectId)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if (! $project->isUmkm()) {
            return back()->with('error', 'Biaya tetap pro-rate hanya untuk unit UMKM.');
        }

        $request->validate([
            'nama' => 'required|string|max:255',
            'amount_monthly' => 'required|string',
            'id_cost_type' => 'nullable|exists:cost_type,id_cost_type',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'catatan' => 'nullable|string',
        ]);

        $amount = $this->normalizeDecimal($request->amount_monthly);
        if ($amount <= 0) {
            return back()->withInput()->with('error', 'Nominal bulanan harus > 0.');
        }

        FixedCost::create([
            'id_perusahaan' => $companyId,
            'id_project' => $project->id_project,
            'id_cost_type' => $request->id_cost_type,
            'nama' => $request->nama,
            'amount_monthly' => $amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => true,
            'catatan' => $request->catatan,
        ]);

        return back()->with('success', 'Biaya tetap ditambahkan. Pro-rate harian otomatis dihitung.');
    }

    public function update(Request $request, $projectId, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $projectId)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $fixed = FixedCost::where('id_fixed_cost', $id)
            ->where('id_project', $project->id_project)
            ->firstOrFail();

        $request->validate([
            'nama' => 'required|string|max:255',
            'amount_monthly' => 'required|string',
            'id_cost_type' => 'nullable|exists:cost_type,id_cost_type',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
            'catatan' => 'nullable|string',
        ]);

        $amount = $this->normalizeDecimal($request->amount_monthly);

        $fixed->update([
            'nama' => $request->nama,
            'amount_monthly' => $amount,
            'id_cost_type' => $request->id_cost_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->boolean('is_active', true),
            'catatan' => $request->catatan,
        ]);

        return back()->with('success', 'Biaya tetap diperbarui.');
    }

    public function delete($projectId, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $projectId)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        FixedCost::where('id_fixed_cost', $id)
            ->where('id_project', $project->id_project)
            ->delete();

        return back()->with('success', 'Biaya tetap dihapus.');
    }
}
