<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $units = Unit::where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->orderBy('nama')
            ->get();

        return view('units.index', [
            'title' => 'Unit Master',
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'nama' => 'required|string|max:255',
            'simbol' => 'nullable|string|max:50',
        ]);

        Unit::create([
            'id_perusahaan' => $companyId,
            'nama' => $request->nama,
            'simbol' => $request->simbol,
        ]);

        return redirect()->route('units.index')->with('success', 'Unit berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $unit = Unit::where('id_unit', $id)
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        $request->validate([
            'nama' => 'required|string|max:255',
            'simbol' => 'nullable|string|max:50',
        ]);

        $unit->update([
            'nama' => $request->nama,
            'simbol' => $request->simbol,
        ]);

        return redirect()->route('units.index')->with('success', 'Unit berhasil diperbarui.');
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $unit = Unit::where('id_unit', $id)
            ->where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Unit berhasil dihapus.');
    }
}
