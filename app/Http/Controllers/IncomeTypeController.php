<?php

namespace App\Http\Controllers;

use App\Models\IncomeCategory;
use App\Models\IncomeType;
use App\Models\Unit;
use Illuminate\Http\Request;

class IncomeTypeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $types = IncomeType::where('id_perusahaan', $companyId ?: null)
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        $categories = IncomeCategory::forCompany($companyId)->active()->ordered()->get();
        $categoryLabels = $categories->pluck('nama', 'kode')->toArray();

        $units = Unit::where('deleted_at', null)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->orderBy('nama')
            ->get();

        return view('income-types.index', [
            'title' => 'Tipe Pendapatan',
            'types' => $types,
            'units' => $units,
            'categories' => $categories,
            'categoryLabels' => $categoryLabels,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:50',
            'default_unit' => 'nullable|string|max:50',
        ]);

        $kategori = strtolower($request->kategori ?: 'other');
        $this->ensureCategory($companyId, $kategori);

        IncomeType::create([
            'id_perusahaan' => $companyId,
            'kode' => strtoupper($request->kode),
            'nama' => $request->nama,
            'kategori' => $kategori,
            'default_unit' => $request->default_unit,
        ]);

        return redirect()->route('income-types.index')->with('success', 'Tipe pendapatan ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $type = IncomeType::where('id_income_type', $id)
            ->where('id_perusahaan', $companyId ?: null)
            ->firstOrFail();

        $request->validate([
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:50',
            'default_unit' => 'nullable|string|max:50',
        ]);

        $kategori = strtolower($request->kategori ?: 'other');
        $this->ensureCategory($companyId, $kategori);

        $type->update([
            'kode' => strtoupper($request->kode),
            'nama' => $request->nama,
            'kategori' => $kategori,
            'default_unit' => $request->default_unit,
        ]);

        return redirect()->route('income-types.index')->with('success', 'Tipe pendapatan diperbarui.');
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $type = IncomeType::where('id_income_type', $id)
            ->where('id_perusahaan', $companyId ?: null)
            ->firstOrFail();

        $type->delete();

        return redirect()->route('income-types.index')->with('success', 'Tipe pendapatan dihapus.');
    }

    private function ensureCategory(?int $companyId, string $kode): void
    {
        if (IncomeCategory::forCompany($companyId)->where('kode', $kode)->exists()) {
            return;
        }
        $max = (int) IncomeCategory::forCompany($companyId)->max('urutan');
        IncomeCategory::create([
            'id_perusahaan' => $companyId,
            'kode' => $kode,
            'nama' => ucfirst(str_replace('_', ' ', $kode)),
            'icon' => 'bi-folder',
            'warna' => 'green',
            'urutan' => $max + 1,
            'is_active' => true,
        ]);
    }
}
