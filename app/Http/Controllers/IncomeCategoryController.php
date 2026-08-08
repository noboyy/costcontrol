<?php

namespace App\Http\Controllers;

use App\Models\IncomeCategory;
use App\Models\IncomeType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IncomeCategoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $categories = IncomeCategory::forCompany($companyId)->ordered()->get();
        $typeCounts = IncomeType::where('id_perusahaan', $companyId ?: null)
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori');

        return view('income-categories.index', [
            'title' => 'Kategori Pendapatan',
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
                'nullable', 'string', 'max:50', 'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('income_category', 'kode')->where(fn ($q) => $q->where('id_perusahaan', $companyId)),
            ],
            'icon' => 'nullable|string|max:50',
            'warna' => 'nullable|string|max:20',
            'urutan' => 'nullable|integer|min:0|max:9999',
        ]);

        $kode = $request->kode ? strtolower(trim($request->kode)) : Str::slug($request->nama, '_');
        if ($kode === '' || IncomeCategory::forCompany($companyId)->where('kode', $kode)->exists()) {
            return back()->withInput()->with('error', 'Kode kategori tidak valid / sudah dipakai.');
        }

        $maxOrder = (int) IncomeCategory::forCompany($companyId)->max('urutan');

        IncomeCategory::create([
            'id_perusahaan' => $companyId,
            'kode' => $kode,
            'nama' => $request->nama,
            'icon' => $request->icon ?: 'bi-folder',
            'warna' => $request->warna ?: 'green',
            'urutan' => $request->filled('urutan') ? (int) $request->urutan : $maxOrder + 1,
            'is_active' => true,
        ]);

        return redirect()->route('income-categories.index')->with('success', 'Kategori pendapatan ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $category = IncomeCategory::forCompany($companyId)->where('id_income_category', $id)->firstOrFail();

        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => [
                'required', 'string', 'max:50', 'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('income_category', 'kode')
                    ->where(fn ($q) => $q->where('id_perusahaan', $companyId))
                    ->ignore($category->id_income_category, 'id_income_category'),
            ],
            'icon' => 'nullable|string|max:50',
            'warna' => 'nullable|string|max:20',
            'urutan' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $old = $category->kode;
        $new = strtolower(trim($request->kode));

        $category->update([
            'kode' => $new,
            'nama' => $request->nama,
            'icon' => $request->icon ?: 'bi-folder',
            'warna' => $request->warna ?: 'green',
            'urutan' => $request->filled('urutan') ? (int) $request->urutan : $category->urutan,
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($old !== $new) {
            IncomeType::where('id_perusahaan', $companyId ?: null)
                ->where('kategori', $old)
                ->update(['kategori' => $new]);
        }

        return redirect()->route('income-categories.index')->with('success', 'Kategori pendapatan diperbarui.');
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $category = IncomeCategory::forCompany($companyId)->where('id_income_category', $id)->firstOrFail();
        $used = IncomeType::where('id_perusahaan', $companyId ?: null)->where('kategori', $category->kode)->count();
        if ($used > 0) {
            return back()->with('error', "Masih dipakai {$used} tipe pendapatan.");
        }
        $category->delete();

        return redirect()->route('income-categories.index')->with('success', 'Kategori dihapus.');
    }

    private function iconOptions(): array
    {
        return [
            'bi-folder' => 'Folder',
            'bi-cash-stack' => 'Penjualan',
            'bi-qr-code' => 'Transfer / QRIS',
            'bi-bag-check' => 'Order Online',
            'bi-receipt' => 'Kontrak / Termyn',
            'bi-plus-circle' => 'Tambahan',
            'bi-three-dots' => 'Lainnya',
        ];
    }

    private function colorOptions(): array
    {
        return [
            'green' => 'Hijau',
            'blue' => 'Biru',
            'yellow' => 'Kuning',
            'red' => 'Merah',
            'gray' => 'Abu-abu',
        ];
    }
}
