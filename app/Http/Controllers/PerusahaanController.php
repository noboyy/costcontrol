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
        ]);

        $data = $request->only('nama_perusahaan', 'alamat_lengkap', 'owner');

        if ($request->has('opening_balance')) {
            $data['opening_balance'] = $this->normalizeDecimal($request->input('opening_balance'), 0);
        }

        $company->update($data);

        return redirect()->route('perusahaan.index')->with('success', 'Pengaturan perusahaan diperbarui.');
    }
}
