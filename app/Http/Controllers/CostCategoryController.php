<?php

namespace App\Http\Controllers;

use App\Models\CostCategory;
use App\Models\CostType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CostCategoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $categories = CostCategory::forCompany($companyId)
            ->ordered()
            ->get();

        $typeCounts = CostType::where('id_perusahaan', $companyId ?: null)
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori');

        return view('cost-categories.index', [
            'title' => 'Kategori Biaya',
            'categories' => $categories,
            'typeCounts' => $typeCounts,
            'iconOptions' => $this->iconOptions(),
            'colorOptions' => $this->colorOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('cost_category', 'kode')->where(fn ($q) => $q->where('id_perusahaan', $companyId)),
            ],
            'icon' => 'nullable|string|max:50',
            'warna' => 'nullable|string|max:20',
            'urutan' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ], [
            'kode.regex' => 'Kode hanya boleh huruf kecil, angka, underscore, atau strip.',
            'kode.unique' => 'Kode kategori sudah dipakai.',
        ]);

        $kode = $request->kode
            ? strtolower(trim($request->kode))
            : Str::slug($request->nama, '_');

        if ($kode === '') {
            return back()->withInput()->with('error', 'Kode kategori tidak valid.');
        }

        $exists = CostCategory::forCompany($companyId)->where('kode', $kode)->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Kode kategori sudah dipakai.');
        }

        $maxOrder = (int) CostCategory::forCompany($companyId)->max('urutan');

        CostCategory::create([
            'id_perusahaan' => $companyId,
            'kode' => $kode,
            'nama' => $request->nama,
            'icon' => $request->icon ?: 'bi-folder',
            'warna' => $request->warna ?: 'gray',
            'urutan' => $request->filled('urutan') ? (int) $request->urutan : $maxOrder + 1,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('cost-categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $category = CostCategory::forCompany($companyId)
            ->where('id_cost_category', $id)
            ->firstOrFail();

        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('cost_category', 'kode')
                    ->where(fn ($q) => $q->where('id_perusahaan', $companyId))
                    ->ignore($category->id_cost_category, 'id_cost_category'),
            ],
            'icon' => 'nullable|string|max:50',
            'warna' => 'nullable|string|max:20',
            'urutan' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ], [
            'kode.regex' => 'Kode hanya boleh huruf kecil, angka, underscore, atau strip.',
            'kode.unique' => 'Kode kategori sudah dipakai.',
        ]);

        $oldKode = $category->kode;
        $newKode = strtolower(trim($request->kode));

        $category->update([
            'kode' => $newKode,
            'nama' => $request->nama,
            'icon' => $request->icon ?: 'bi-folder',
            'warna' => $request->warna ?: 'gray',
            'urutan' => $request->filled('urutan') ? (int) $request->urutan : $category->urutan,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Sync cost_type.kategori if code changed
        if ($oldKode !== $newKode) {
            CostType::where('id_perusahaan', $companyId ?: null)
                ->where('kategori', $oldKode)
                ->update(['kategori' => $newKode]);
        }

        return redirect()->route('cost-categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $category = CostCategory::forCompany($companyId)
            ->where('id_cost_category', $id)
            ->firstOrFail();

        $used = CostType::where('id_perusahaan', $companyId ?: null)
            ->where('kategori', $category->kode)
            ->count();

        if ($used > 0) {
            return back()->with('error', "Kategori masih dipakai {$used} tipe biaya. Pindahkan dulu sebelum hapus.");
        }

        $category->delete();

        return redirect()->route('cost-categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    private function iconOptions(): array
    {
        return [
            'bi-folder' => 'Folder',
            'bi-bricks' => 'Material',
            'bi-basket' => 'Bahan Baku',
            'bi-people' => 'Tenaga Kerja',
            'bi-gear' => 'Peralatan',
            'bi-truck' => 'Transport',
            'bi-tools' => 'Jasa',
            'bi-building' => 'Overhead / Tetap',
            'bi-cash-coin' => 'Operasional Harian',
            'bi-receipt' => 'Pajak',
            'bi-cash-stack' => 'Keuangan',
            'bi-box-seam' => 'Barang',
            'bi-shop' => 'UMKM / Outlet',
            'bi-lightning' => 'Utilitas',
            'bi-three-dots' => 'Lainnya',
        ];
    }

    private function colorOptions(): array
    {
        return [
            'blue' => 'Biru',
            'green' => 'Hijau',
            'yellow' => 'Kuning',
            'red' => 'Merah',
            'gray' => 'Abu-abu',
        ];
    }
}
