<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDecimal;
use App\Models\Perusahaan;
use Illuminate\Http\Request;

class PerusahaanController extends Controller
{
    use HandlesDecimal;

    /**
     * Pengaturan perusahaan — single tenant (Sahla Journey).
     * Menampilkan form edit untuk satu-satunya record perusahaan.
     */
    public function index()
    {
        $perusahaan = Perusahaan::first();

        return view('perusahaan.index', [
            'title' => 'Pengaturan',
            'perusahaan' => $perusahaan,
        ]);
    }

    public function update(Request $request, $id)
    {
        $company = Perusahaan::findOrFail($id);

        $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'alamat_lengkap' => 'nullable|string',
            'owner' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|string',
            'mode_pajak' => ['required', 'in:'.implode(',', [Perusahaan::PAJAK_FINAL_OMZET, Perusahaan::PAJAK_BADAN_LABA])],
            'tarif_pph_omzet' => 'nullable|string',
            'tarif_pph_laba' => 'nullable|string',
            'pajak_aktif' => 'nullable|boolean',
        ]);

        $data = $request->only('nama_perusahaan', 'alamat_lengkap', 'owner');

        if ($request->has('opening_balance')) {
            $data['opening_balance'] = $this->normalizeDecimal($request->input('opening_balance'), 0);
        }
        if ($request->has('mode_pajak')) {
            $data['mode_pajak'] = $request->input('mode_pajak');
        }
        if ($request->has('tarif_pph_omzet')) {
            $data['tarif_pph_omzet'] = $this->normalizeDecimal($request->input('tarif_pph_omzet'), 0.50);
        }
        if ($request->has('tarif_pph_laba')) {
            $data['tarif_pph_laba'] = $this->normalizeDecimal($request->input('tarif_pph_laba'), 22.00);
        }
        if ($request->has('pajak_aktif')) {
            $data['pajak_aktif'] = $request->boolean('pajak_aktif');
        }

        $company->update($data);

        return redirect()->route('perusahaan.index')->with('success', 'Pengaturan perusahaan diperbarui.');
    }
}
