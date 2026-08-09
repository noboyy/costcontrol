<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostCategory;
use App\Models\CostType;
use App\Models\IncomeCategory;
use App\Models\IncomeType;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(Request $request, string $kind)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        if ($kind === 'cost') {
            $types = CostType::where('id_perusahaan', $companyId ?: null)->orderBy('nama')->get();
            $categories = CostCategory::forCompany($companyId)->active()->ordered()->get();
        } else {
            $types = IncomeType::where('id_perusahaan', $companyId ?: null)->orderBy('nama')->get();
            $categories = IncomeCategory::forCompany($companyId)->active()->ordered()->get();
        }

        $categoryMeta = $categories->mapWithKeys(fn ($c) => [
            $c->kode => ['nama' => $c->nama, 'icon' => $c->icon, 'warna' => $c->warna],
        ])->toArray();

        $grouped = $types->groupBy(function ($t) {
            $key = strtolower(trim((string) ($t->kategori ?: 'other')));

            return $key !== '' ? $key : 'other';
        });

        $result = [];
        foreach ($grouped as $key => $list) {
            $result[] = [
                'kode' => $key,
                'nama' => $categoryMeta[$key]['nama'] ?? ucfirst(str_replace('_', ' ', $key)),
                'icon' => $categoryMeta[$key]['icon'] ?? null,
                'warna' => $categoryMeta[$key]['warna'] ?? null,
                'types' => $list->sortBy('nama')->values()->map(fn ($t) => [
                    'id' => $kind === 'cost' ? $t->id_cost_type : $t->id_income_type,
                    'kode' => $t->kode,
                    'nama' => $t->nama,
                    'kategori' => $t->kategori,
                    'default_unit' => $t->default_unit,
                ]),
            ];
        }

        foreach ($categories as $cat) {
            if (! collect($result)->contains('kode', $cat->kode)) {
                $result[] = [
                    'kode' => $cat->kode,
                    'nama' => $cat->nama,
                    'icon' => $cat->icon,
                    'warna' => $cat->warna,
                    'types' => [],
                ];
            }
        }

        return response()->json([
            'kind' => $kind,
            'categories' => $result,
        ]);
    }

    public function store(Request $request, string $kind)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:50',
            'default_unit' => 'nullable|string|max:50',
        ]);

        $kategori = strtolower($request->kategori ?: 'other');
        $this->ensureCategory($kind, $companyId, $kategori);

        if ($kind === 'cost') {
            $type = CostType::create([
                'id_perusahaan' => $companyId,
                'kode' => strtoupper($request->kode),
                'nama' => $request->nama,
                'kategori' => $kategori,
                'default_unit' => $request->default_unit,
            ]);
            $key = 'id_cost_type';
        } else {
            $type = IncomeType::create([
                'id_perusahaan' => $companyId,
                'kode' => strtoupper($request->kode),
                'nama' => $request->nama,
                'kategori' => $kategori,
                'default_unit' => $request->default_unit,
            ]);
            $key = 'id_income_type';
        }

        return response()->json([
            'message' => 'Tipe berhasil ditambahkan.',
            'id' => $type[$key],
        ], 201);
    }

    public function update(Request $request, string $kind, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $request->validate([
            'kode' => 'required|string|max:50',
            'nama' => 'required|string|max:255',
            'kategori' => 'nullable|string|max:50',
            'default_unit' => 'nullable|string|max:50',
        ]);

        $kategori = strtolower($request->kategori ?: 'other');
        $this->ensureCategory($kind, $companyId, $kategori);

        if ($kind === 'cost') {
            $type = CostType::where('id_cost_type', $id)->where('id_perusahaan', $companyId ?: null)->firstOrFail();
        } else {
            $type = IncomeType::where('id_income_type', $id)->where('id_perusahaan', $companyId ?: null)->firstOrFail();
        }

        $type->update([
            'kode' => strtoupper($request->kode),
            'nama' => $request->nama,
            'kategori' => $kategori,
            'default_unit' => $request->default_unit,
        ]);

        return response()->json(['message' => 'Tipe diperbarui.']);
    }

    public function delete(Request $request, string $kind, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        if ($kind === 'cost') {
            CostType::where('id_cost_type', $id)->where('id_perusahaan', $companyId ?: null)->firstOrFail()->delete();
        } else {
            IncomeType::where('id_income_type', $id)->where('id_perusahaan', $companyId ?: null)->firstOrFail()->delete();
        }

        return response()->json(['message' => 'Tipe dihapus.']);
    }

    private function ensureCategory(string $kind, ?int $companyId, string $kode): void
    {
        if ($kind === 'cost') {
            if (CostCategory::forCompany($companyId)->where('kode', $kode)->exists()) {
                return;
            }
            $max = (int) CostCategory::forCompany($companyId)->max('urutan');
            CostCategory::create([
                'id_perusahaan' => $companyId,
                'kode' => $kode,
                'nama' => ucfirst(str_replace('_', ' ', $kode)),
                'icon' => 'bi-folder',
                'warna' => 'gray',
                'urutan' => $max + 1,
                'is_active' => true,
            ]);
        } else {
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
}
