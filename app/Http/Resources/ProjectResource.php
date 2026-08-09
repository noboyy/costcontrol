<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_project' => $this->id_project,
            'id_perusahaan' => $this->id_perusahaan,
            'nama_project' => $this->nama_project,
            'client' => $this->client,
            'lokasi' => $this->lokasi,
            'date_start' => $this->date_start?->format('Y-m-d'),
            'date_end' => $this->date_end?->format('Y-m-d'),
            'project_value' => $this->project_value !== null ? (float) $this->project_value : null,
            'status' => $this->status,
            'mode' => $this->mode,
            'budget_period' => $this->budget_period,
            'daily_budget' => $this->daily_budget !== null ? (float) $this->daily_budget : null,
            'monthly_budget' => $this->monthly_budget !== null ? (float) $this->monthly_budget : null,
            'business_type' => $this->business_type,
            'cogs_ratio_alert' => $this->cogs_ratio_alert !== null ? (float) $this->cogs_ratio_alert : null,
            'lock_closed_days' => $this->lock_closed_days,
            'is_umkm' => $this->isUmkm(),
            'is_archived' => $this->isArchived(),
            'admins' => $this->whenLoaded('admins', fn () => $this->admins->map(fn ($a) => [
                'id_pengguna' => $a->id_pengguna,
                'nama_lengkap' => $a->nama_lengkap,
            ])),
        ];
    }
}
