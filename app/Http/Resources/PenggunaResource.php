<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenggunaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pengguna' => $this->id_pengguna,
            'id_perusahaan' => $this->id_perusahaan,
            'nama_lengkap' => $this->nama_lengkap,
            'no_hp' => $this->no_hp,
            'alamat' => $this->alamat,
            'jabatan' => $this->jabatan,
        ];
    }
}