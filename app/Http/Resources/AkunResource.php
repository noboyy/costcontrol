<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AkunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_akun' => $this->id_akun,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'nama_lengkap' => $this->nama_lengkap,
            'id_perusahaan' => $this->id_perusahaan,
            'profile_photo_url' => $this->profile_photo_url,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'is_trial_expired' => $this->isTrialExpired(),
            'pengguna' => $this->whenLoaded('pengguna', fn () => new PenggunaResource($this->pengguna)),
        ];
    }
}