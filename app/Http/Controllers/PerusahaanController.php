<?php

namespace App\Http\Controllers;

use App\Models\Perusahaan;
use Illuminate\Http\Request;

class PerusahaanController extends Controller
{
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
        ]);

        $company->update($request->only('nama_perusahaan', 'alamat_lengkap', 'owner'));

        return redirect()->route('perusahaan.index')->with('success', 'Pengaturan perusahaan diperbarui.');
    }
}
