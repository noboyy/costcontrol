<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_cost_type' => 'required|exists:cost_type,id_cost_type',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'harga_satuan' => 'nullable|string',
            'total' => 'nullable|string',
            'catatan' => 'nullable|string',
            'file_bukti' => 'nullable|file|mimes:jpg,png,pdf|max:2048',
        ];
    }
}
