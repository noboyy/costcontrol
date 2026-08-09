<?php

namespace App\Http\Controllers;

use App\Models\CostCategory;
use App\Models\CostEntry;
use App\Models\CostType;
use App\Models\Unit;
use Illuminate\Http\Request;

class CostTypeController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->masterDataCompanyId();

        $types = CostType::where('id_perusahaan', $companyId ?: null)
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        $categories = CostCategory::forCompany($companyId)
            ->active()
            ->ordered()
            ->get();

        // Fallback labels if category master empty
        $categoryLabels = $categories->pluck('nama', 'kode')->toArray();
        $categoryMeta = $categories->mapWithKeys(function ($c) {
            return [$c->kode => [
                'icon' => $c->icon ?: 'bi-folder',
                'color' => $c->warna ?: 'gray',
                'nama' => $c->nama,
                'urutan' => $c->urutan,
            ]];
        })->toArray();

        $categoryOrder = $categories->pluck('kode')->values()->all();

        $grouped = $types->groupBy(function ($type) {
            $key = strtolower(trim((string) ($type->kategori ?: 'other')));

            return $key !== '' ? $key : 'other';
        });

        $sortedKeys = $grouped->keys()->sortBy(function ($key) use ($categoryOrder) {
            $idx = array_search($key, $categoryOrder, true);

            return $idx === false ? 1000 + ord($key[0] ?? 'z') : $idx;
        })->values();

        $typesByCategory = collect();
        foreach ($sortedKeys as $key) {
            $typesByCategory->put($key, $grouped->get($key)->sortBy('nama')->values());
            if (! isset($categoryLabels[$key])) {
                $categoryLabels[$key] = ucfirst(str_replace('_', ' ', $key));
            }
            if (! isset($categoryMeta[$key])) {
                $categoryMeta[$key] = [
                    'icon' => 'bi-folder',
                    'color' => 'gray',
                    'nama' => $categoryLabels[$key],
                    'urutan' => 999,
                ];
            }
        }

        // Include empty active categories so user sees structure
        foreach ($categories as $cat) {
            if (! $typesByCategory->has($cat->kode)) {
                $typesByCategory->put($cat->kode, collect());
            }
        }

        // Re-sort by category order including empty ones
        $typesByCategory = $typesByCategory->sortBy(function ($items, $key) use ($categoryOrder) {
            $idx = array_search($key, $categoryOrder, true);

            return $idx === false ? 1000 : $idx;
        });

        $units = Unit::where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->orderBy('nama')
            ->get();

        return view('cost-types.index', [
            'title' => 'Tipe Biaya',
            'types' => $types,
            'typesByCategory' => $typesByCategory,
            'categoryLabels' => $categoryLabels,
            'categoryMeta' => $categoryMeta,
            'categories' => $categories,
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->masterDataCompanyId();

        $request->validate([
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'kategori' => 'required|string|max:50',
            'default_unit' => 'nullable|string|max:50',
        ]);

        $kategori = strtolower($request->kategori);
        $this->ensureCategoryExists($companyId, $kategori);

        CostType::create([
            'id_perusahaan' => $companyId,
            'kode' => strtoupper($request->kode),
            'nama' => $request->nama,
            'kategori' => $kategori,
            'default_unit' => $request->default_unit,
        ]);

        return redirect()->route('cost-types.index')->with('success', 'Tipe biaya berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->masterDataCompanyId();

        $type = CostType::where('id_cost_type', $id)
            ->where('id_perusahaan', $companyId ?: null)
            ->firstOrFail();

        $request->validate([
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'kategori' => 'required|string|max:50',
            'default_unit' => 'nullable|string|max:50',
        ]);

        $kategori = strtolower($request->kategori);
        $this->ensureCategoryExists($companyId, $kategori);

        $type->update([
            'kode' => strtoupper($request->kode),
            'nama' => $request->nama,
            'kategori' => $kategori,
            'default_unit' => $request->default_unit,
        ]);

        return redirect()->route('cost-types.index')->with('success', 'Tipe biaya berhasil diperbarui.');
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->masterDataCompanyId();

        $type = CostType::where('id_cost_type', $id)
            ->where('id_perusahaan', $companyId ?: null)
            ->firstOrFail();

        if (CostEntry::where('id_cost_type', $id)->exists()) {
            return back()->with('error', 'Tipe biaya masih digunakan oleh entri biaya. Hapus entri terkait dulu.');
        }

        $type->delete();

        return redirect()->route('cost-types.index')->with('success', 'Tipe biaya berhasil dihapus.');
    }

    private function ensureCategoryExists(?int $companyId, string $kode): void
    {
        $exists = CostCategory::forCompany($companyId)->where('kode', $kode)->exists();
        if ($exists) {
            return;
        }

        $maxOrder = (int) CostCategory::forCompany($companyId)->max('urutan');

        CostCategory::create([
            'id_perusahaan' => $companyId,
            'kode' => $kode,
            'nama' => ucfirst(str_replace('_', ' ', $kode)),
            'icon' => 'bi-folder',
            'warna' => 'gray',
            'urutan' => $maxOrder + 1,
            'is_active' => true,
        ]);
    }
}
