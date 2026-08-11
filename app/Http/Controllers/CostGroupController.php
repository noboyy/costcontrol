<?php

namespace App\Http\Controllers;

use App\Models\CostCategory;
use App\Models\CostGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CostGroupController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $companyId = $user->masterDataCompanyId();

        $groups = CostGroup::forCompany($companyId)
            ->ordered()
            ->get();

        $categoryCounts = CostCategory::where('id_perusahaan', $companyId ?: null)
            ->whereNotNull('kelompok')
            ->selectRaw('kelompok, COUNT(*) as total')
            ->groupBy('kelompok')
            ->pluck('total', 'kelompok');

        return view('cost-groups.index', [
            'title' => 'Kelompok Biaya',
            'groups' => $groups,
            'categoryCounts' => $categoryCounts,
            'colorOptions' => $this->colorOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->masterDataCompanyId();

        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('cost_group', 'kode')->where(fn ($q) => $q->where('id_perusahaan', $companyId)),
            ],
            'warna' => 'nullable|string|max:20',
            'urutan' => 'nullable|integer|min:0|max:9999',
        ], [
            'kode.regex' => 'Kode hanya boleh huruf kecil, angka, underscore, atau strip.',
            'kode.unique' => 'Kode kelompok sudah dipakai.',
        ]);

        $kode = $request->kode
            ? strtolower(trim($request->kode))
            : Str::slug($request->nama, '_');

        if ($kode === '') {
            return back()->withInput()->with('error', 'Kode kelompok tidak valid.');
        }

        $exists = CostGroup::forCompany($companyId)->where('kode', $kode)->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Kode kelompok sudah dipakai.');
        }

        $maxOrder = (int) CostGroup::forCompany($companyId)->max('urutan');

        CostGroup::create([
            'id_perusahaan' => $companyId,
            'kode' => $kode,
            'nama' => $request->nama,
            'warna' => $request->warna ?: 'gray',
            'urutan' => $request->filled('urutan') ? (int) $request->urutan : $maxOrder + 1,
            'is_active' => true,
        ]);

        return redirect()->route('cost-groups.index')->with('success', 'Kelompok berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->masterDataCompanyId();

        $group = CostGroup::forCompany($companyId)
            ->where('id_cost_group', $id)
            ->firstOrFail();

        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('cost_group', 'kode')
                    ->where(fn ($q) => $q->where('id_perusahaan', $companyId))
                    ->ignore($group->id_cost_group, 'id_cost_group'),
            ],
            'warna' => 'nullable|string|max:20',
            'urutan' => 'nullable|integer|min:0|max:9999',
        ], [
            'kode.regex' => 'Kode hanya boleh huruf kecil, angka, underscore, atau strip.',
            'kode.unique' => 'Kode kelompok sudah dipakai.',
        ]);

        $oldKode = $group->kode;
        $newKode = strtolower(trim($request->kode));

        $group->update([
            'kode' => $newKode,
            'nama' => $request->nama,
            'warna' => $request->warna ?: 'gray',
            'urutan' => $request->filled('urutan') ? (int) $request->urutan : $group->urutan,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Sync cost_category.kelompok if code changed
        if ($oldKode !== $newKode) {
            CostCategory::where('id_perusahaan', $companyId ?: null)
                ->where('kelompok', $oldKode)
                ->update(['kelompok' => $newKode]);
        }

        return redirect()->route('cost-groups.index')->with('success', 'Kelompok berhasil diperbarui.');
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->masterDataCompanyId();

        $group = CostGroup::forCompany($companyId)
            ->where('id_cost_group', $id)
            ->firstOrFail();

        $used = CostCategory::where('id_perusahaan', $companyId ?: null)
            ->where('kelompok', $group->kode)
            ->count();

        if ($used > 0) {
            return back()->with('error', "Kelompok masih dipakai {$used} kategori. Pindahkan dulu sebelum hapus.");
        }

        $group->delete();

        return redirect()->route('cost-groups.index')->with('success', 'Kelompok berhasil dihapus.');
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