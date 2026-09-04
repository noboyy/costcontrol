<?php

namespace App\Services;

use App\Models\Project;
use App\Models\CostEntry;
use App\Models\IncomeEntry;
use Illuminate\Support\Facades\DB;

class ProjectManagementService
{
    public function store(array $data, int $companyId): Project
    {
        return DB::transaction(function () use ($data, $companyId) {
            $project = Project::create([
                'id_perusahaan' => $companyId,
                'nama_project' => $data['nama_project'],
                'client' => $data['client'] ?? null,
                'lokasi' => $data['lokasi'],
                'date_start' => $data['date_start'] ?? null,
                'date_end' => $data['date_end'] ?? null,
                'project_value' => $this->normalizeDecimal($data['project_value'] ?? null),
                'status' => 'active',
                'mode' => Project::MODE_PROJECT,
                'budget_period' => $data['budget_period'] ?: Project::BUDGET_TOTAL,
                'daily_budget' => $this->normalizeDecimal($data['daily_budget'] ?? null),
                'monthly_budget' => $this->normalizeDecimal($data['monthly_budget'] ?? null),
            ]);

            return $project;
        });
    }

    public function update(Project $project, array $data): Project
    {
        if ($project->isArchived()) {
            throw new \Exception('Project sudah diarsipkan.');
        }

        $data = collect($data)->filter(fn($v) => $v !== null && $v !== '')->toArray();

        $data['project_value'] = isset($data['project_value']) ? $this->normalizeDecimal($data['project_value']) : $project->project_value;
        $data['budget_period'] = $data['budget_period'] ?? $project->budget_period;
        $data['daily_budget'] = $data['daily_budget'] ?? $project->daily_budget;
        $data['monthly_budget'] = $data['monthly_budget'] ?? $project->monthly_budget;

        $project->update(array_filter($data));

        return $project->fresh();
    }

    public function addCost(Project $project, array $data, bool $hasFile = false): CostEntry
    {
        if ($project->isArchived()) {
            throw new \Exception('Project sudah diarsipkan.');
        }

        $cost = CostEntry::create([
            'id_perusahaan' => $project->id_perusahaan,
            'id_project' => $project->id_project,
            'id_cost_type' => $data['id_cost_type'],
            'tanggal' => $data['tanggal'],
            'keterangan' => $data['keterangan'] ?? null,
            'qty' => $this->normalizeDecimal($data['qty']),
            'unit' => $data['unit'] ?? null,
            'harga_satuan' => $this->normalizeDecimal($data['harga_satuan'] ?? 0),
            'total' => $this->normalizeDecimal($data['total'] ?? ($data['qty'] * $data['harga_satuan'])),
            'catatan' => $data['catatan'] ?? null,
        ]);

        if ($hasFile) {
            $cost->update(['file_bukti' => $data['file_bukti']]);
        }

        return $cost;
    }

    public function updateCost(CostEntry $cost, array $data): CostEntry
    {
        if ($cost->project()->first()?->isArchived()) {
            throw new \Exception('Project sudah diarsipkan.');
        }

        $cost->update([
            'id_cost_type' => $data['id_cost_type'],
            'tanggal' => $data['tanggal'],
            'keterangan' => $data['keterangan'] ?? null,
            'qty' => $this->normalizeDecimal($data['qty']),
            'unit' => $data['unit'] ?? null,
            'harga_satuan' => $this->normalizeDecimal($data['harga_satuan']),
            'total' => $this->normalizeDecimal($data['total']),
            'catatan' => $data['catatan'] ?? null,
        ]);

        return $cost->fresh();
    }

    public function deleteCost(CostEntry $cost): void
    {
        $cost->delete();
    }

    private function normalizeDecimal(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = str_replace(['.', ' '], ['', ''], (string) $value);
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }
}
