<?php

namespace App\Services;

use App\Models\CostCategory;
use App\Models\CostEntry;
use App\Models\CostGroup;
use App\Models\CostType;
use App\Models\IncomeCategory;
use App\Models\IncomeEntry;
use App\Models\IncomeType;
use App\Models\Unit;
use Illuminate\Support\Collection;

class MasterDataModuleService
{
    public const MODULES = [
        'cost_groups' => 'Kelompok Biaya',
        'cost_categories' => 'Kategori Biaya',
        'cost_types' => 'Tipe Biaya',
        'income_categories' => 'Kategori Pendapatan',
        'income_types' => 'Tipe Pendapatan',
        'units' => 'Satuan',
    ];

    public const MODES = ['add', 'update'];

    /**
     * Copy selectable master data modules (global, id_perusahaan = NULL) into a tenant company.
     *
     * @return array{added:int, existing:int, updated:int, deleted:int}
     */
    public function copyModulesToCompany(int $companyId, array $modules = [], string $mode = 'add'): array
    {
        $modules = $modules ?: array_keys(self::MODULES);
        $mode = in_array($mode, self::MODES, true) ? $mode : 'add';

        $stats = ['added' => 0, 'existing' => 0, 'updated' => 0, 'deleted' => 0];

        $steps = [
            'cost_groups' => 'syncCostGroups',
            'cost_categories' => 'syncCostCategories',
            'income_categories' => 'syncIncomeCategories',
            'cost_types' => 'syncCostTypes',
            'income_types' => 'syncIncomeTypes',
            'units' => 'syncUnits',
        ];

        foreach ($steps as $key => $method) {
            if (in_array($key, $modules, true)) {
                $stats = $this->mergeStats($stats, $this->{$method}($companyId, $mode));
            }
        }

        return $stats;
    }

    /**
     * Global module item counts per module, for UI feedback.
     *
     * @return array<string, int>
     */
    public function moduleCounts(): array
    {
        return [
            'cost_groups' => CostGroup::where('id_perusahaan', null)->count(),
            'cost_categories' => CostCategory::where('id_perusahaan', null)->count(),
            'cost_types' => CostType::where('id_perusahaan', null)->count(),
            'income_categories' => IncomeCategory::where('id_perusahaan', null)->count(),
            'income_types' => IncomeType::where('id_perusahaan', null)->count(),
            'units' => Unit::where('id_perusahaan', null)->count(),
        ];
    }

    /**
     * Seed the global module library from an existing company.
     */
    public function seedGlobalFromCompany(int $sourceCompanyId): void
    {
        foreach (CostGroup::where('id_perusahaan', $sourceCompanyId)->get() as $row) {
            CostGroup::firstOrCreate([
                'id_perusahaan' => null,
                'kode' => $row->kode,
            ], [
                'nama' => $row->nama,
                'warna' => $row->warna,
                'urutan' => $row->urutan,
                'is_active' => $row->is_active,
            ]);
        }

        foreach (CostCategory::where('id_perusahaan', $sourceCompanyId)->get() as $row) {
            CostCategory::firstOrCreate([
                'id_perusahaan' => null,
                'kode' => $row->kode,
            ], [
                'nama' => $row->nama,
                'icon' => $row->icon,
                'warna' => $row->warna,
                'urutan' => $row->urutan,
                'is_active' => $row->is_active,
                'kelompok' => $row->kelompok,
            ]);
        }

        foreach (IncomeCategory::where('id_perusahaan', $sourceCompanyId)->get() as $row) {
            IncomeCategory::firstOrCreate([
                'id_perusahaan' => null,
                'kode' => $row->kode,
            ], [
                'nama' => $row->nama,
                'icon' => $row->icon,
                'warna' => $row->warna,
                'urutan' => $row->urutan,
                'is_active' => $row->is_active,
            ]);
        }

        foreach (CostType::where('id_perusahaan', $sourceCompanyId)->get() as $row) {
            CostType::firstOrCreate([
                'id_perusahaan' => null,
                'kode' => $row->kode,
            ], [
                'nama' => $row->nama,
                'kategori' => $row->kategori,
                'default_unit' => $row->default_unit,
            ]);
        }

        foreach (IncomeType::where('id_perusahaan', $sourceCompanyId)->get() as $row) {
            IncomeType::firstOrCreate([
                'id_perusahaan' => null,
                'kode' => $row->kode,
            ], [
                'nama' => $row->nama,
                'kategori' => $row->kategori,
                'default_unit' => $row->default_unit,
            ]);
        }

        foreach (Unit::where('id_perusahaan', $sourceCompanyId)->get() as $row) {
            Unit::firstOrCreate([
                'id_perusahaan' => null,
                'nama' => $row->nama,
            ], [
                'simbol' => $row->simbol,
            ]);
        }
    }

    private function mergeStats(array $a, array $b): array
    {
        foreach ($b as $k => $v) {
            $a[$k] += $v;
        }

        return $a;
    }

    private function syncCostGroups(int $companyId, string $mode): array
    {
        $stats = ['added' => 0, 'existing' => 0, 'updated' => 0, 'deleted' => 0];
        $global = CostGroup::where('id_perusahaan', null)->orderBy('urutan')->get();
        $globalKodes = $global->map(fn ($r) => strtolower((string) $r->kode))->all();

        $company = CostGroup::where('id_perusahaan', $companyId)->get();
        $map = $company->keyBy(fn ($r) => strtolower((string) $r->kode));

        foreach ($global as $row) {
            $key = strtolower((string) $row->kode);
            $group = $map->get($key);
            if ($group) {
                $stats['existing']++;
                if ($mode === 'update') {
                    $group->update([
                        'nama' => $row->nama,
                        'warna' => $row->warna,
                        'urutan' => $row->urutan,
                        'is_active' => $row->is_active,
                    ]);
                    $stats['updated']++;
                }
            } else {
                CostGroup::create([
                    'id_perusahaan' => $companyId,
                    'kode' => $row->kode,
                    'nama' => $row->nama,
                    'warna' => $row->warna,
                    'urutan' => $row->urutan,
                    'is_active' => $row->is_active,
                ]);
                $stats['added']++;
            }
        }

        if ($mode === 'update') {
            $usedKodes = CostCategory::where('id_perusahaan', $companyId)
                ->pluck('kelompok')
                ->map(fn ($k) => strtolower(trim((string) $k)))
                ->unique()
                ->all();
            $orphans = $company->reject(fn ($r) => in_array(strtolower((string) $r->kode), $globalKodes, true))
                ->reject(fn ($r) => in_array(strtolower((string) $r->kode), $usedKodes, true));
            foreach ($orphans as $o) {
                $o->delete();
                $stats['deleted']++;
            }
        }

        return $stats;
    }

    private function syncCostCategories(int $companyId, string $mode): array
    {
        $stats = ['added' => 0, 'existing' => 0, 'updated' => 0, 'deleted' => 0];
        $global = CostCategory::where('id_perusahaan', null)->get();
        $globalKodes = $global->map(fn ($r) => strtolower((string) $r->kode))->all();

        $company = CostCategory::where('id_perusahaan', $companyId)->get();
        $map = $company->keyBy(fn ($r) => strtolower((string) $r->kode));

        foreach ($global as $row) {
            $key = strtolower((string) $row->kode);
            $cat = $map->get($key);
            if ($row->kelompok) {
                $this->ensureCostGroup($companyId, $row->kelompok);
            }
            if ($cat) {
                $stats['existing']++;
                if ($mode === 'update' && $this->categoryDirty($cat, $row)) {
                    $cat->update([
                        'nama' => $row->nama,
                        'icon' => $row->icon,
                        'warna' => $row->warna,
                        'urutan' => $row->urutan,
                        'is_active' => $row->is_active,
                        'kelompok' => $row->kelompok,
                    ]);
                    $stats['updated']++;
                }
            } else {
                CostCategory::create([
                    'id_perusahaan' => $companyId,
                    'kode' => $row->kode,
                    'nama' => $row->nama,
                    'icon' => $row->icon,
                    'warna' => $row->warna,
                    'urutan' => $row->urutan,
                    'is_active' => $row->is_active,
                    'kelompok' => $row->kelompok,
                ]);
                $stats['added']++;
            }
        }

        if ($mode === 'update') {
            $usedKodes = CostType::where('id_perusahaan', $companyId)
                ->pluck('kategori')
                ->map(fn ($k) => strtolower(trim((string) $k)))
                ->unique()
                ->all();

            $orphans = $company->reject(fn ($r) => in_array(strtolower((string) $r->kode), $globalKodes, true))
                ->reject(fn ($r) => in_array(strtolower((string) $r->kode), $usedKodes, true));

            foreach ($orphans as $o) {
                $o->delete();
                $stats['deleted']++;
            }
        }

        return $stats;
    }

    private function syncIncomeCategories(int $companyId, string $mode): array
    {
        $stats = ['added' => 0, 'existing' => 0, 'updated' => 0, 'deleted' => 0];
        $global = IncomeCategory::where('id_perusahaan', null)->get();
        $globalKodes = $global->map(fn ($r) => strtolower((string) $r->kode))->all();

        $company = IncomeCategory::where('id_perusahaan', $companyId)->get();
        $map = $company->keyBy(fn ($r) => strtolower((string) $r->kode));

        foreach ($global as $row) {
            $key = strtolower((string) $row->kode);
            $cat = $map->get($key);
            if ($cat) {
                $stats['existing']++;
                if ($mode === 'update' && $this->categoryDirty($cat, $row)) {
                    $cat->update([
                        'nama' => $row->nama,
                        'icon' => $row->icon,
                        'warna' => $row->warna,
                        'urutan' => $row->urutan,
                        'is_active' => $row->is_active,
                    ]);
                    $stats['updated']++;
                }
            } else {
                IncomeCategory::create([
                    'id_perusahaan' => $companyId,
                    'kode' => $row->kode,
                    'nama' => $row->nama,
                    'icon' => $row->icon,
                    'warna' => $row->warna,
                    'urutan' => $row->urutan,
                    'is_active' => $row->is_active,
                ]);
                $stats['added']++;
            }
        }

        if ($mode === 'update') {
            $usedKodes = IncomeType::where('id_perusahaan', $companyId)
                ->pluck('kategori')
                ->map(fn ($k) => strtolower(trim((string) $k)))
                ->unique()
                ->all();

            $orphans = $company->reject(fn ($r) => in_array(strtolower((string) $r->kode), $globalKodes, true))
                ->reject(fn ($r) => in_array(strtolower((string) $r->kode), $usedKodes, true));

            foreach ($orphans as $o) {
                $o->delete();
                $stats['deleted']++;
            }
        }

        return $stats;
    }

    private function syncCostTypes(int $companyId, string $mode): array
    {
        $stats = ['added' => 0, 'existing' => 0, 'updated' => 0, 'deleted' => 0];
        $global = CostType::where('id_perusahaan', null)->get();
        $globalKodes = $global->map(fn ($r) => strtolower((string) $r->kode))->all();

        $company = CostType::where('id_perusahaan', $companyId)->get();
        $map = $company->keyBy(fn ($r) => strtolower((string) $r->kode));

        foreach ($global as $row) {
            $key = strtolower((string) $row->kode);
            $type = $map->get($key);

            if ($this->ensureCostCategory($companyId, $row->kategori)) {
                $stats['added']++;
            }
            if ($this->ensureUnit($companyId, $row->default_unit)) {
                $stats['added']++;
            }

            if ($type) {
                $stats['existing']++;
                if ($mode === 'update' && $this->typeDirty($type, $row)) {
                    $type->update([
                        'nama' => $row->nama,
                        'kategori' => $row->kategori,
                        'default_unit' => $row->default_unit,
                    ]);
                    $stats['updated']++;
                }
            } else {
                CostType::create([
                    'id_perusahaan' => $companyId,
                    'kode' => $row->kode,
                    'nama' => $row->nama,
                    'kategori' => $row->kategori,
                    'default_unit' => $row->default_unit,
                ]);
                $stats['added']++;
            }
        }

        if ($mode === 'update') {
            $usedIds = CostEntry::where('id_perusahaan', $companyId)->pluck('id_cost_type')->unique()->all();
            $orphans = $company->reject(fn ($r) => in_array(strtolower((string) $r->kode), $globalKodes, true))
                ->reject(fn ($r) => in_array($r->id_cost_type, $usedIds, true));
            foreach ($orphans as $o) {
                $o->delete();
                $stats['deleted']++;
            }
        }

        return $stats;
    }

    private function syncIncomeTypes(int $companyId, string $mode): array
    {
        $stats = ['added' => 0, 'existing' => 0, 'updated' => 0, 'deleted' => 0];
        $global = IncomeType::where('id_perusahaan', null)->get();
        $globalKodes = $global->map(fn ($r) => strtolower((string) $r->kode))->all();

        $company = IncomeType::where('id_perusahaan', $companyId)->get();
        $map = $company->keyBy(fn ($r) => strtolower((string) $r->kode));

        foreach ($global as $row) {
            $key = strtolower((string) $row->kode);
            $type = $map->get($key);

            if ($this->ensureIncomeCategory($companyId, $row->kategori)) {
                $stats['added']++;
            }
            if ($this->ensureUnit($companyId, $row->default_unit)) {
                $stats['added']++;
            }

            if ($type) {
                $stats['existing']++;
                if ($mode === 'update' && $this->typeDirty($type, $row)) {
                    $type->update([
                        'nama' => $row->nama,
                        'kategori' => $row->kategori,
                        'default_unit' => $row->default_unit,
                    ]);
                    $stats['updated']++;
                }
            } else {
                IncomeType::create([
                    'id_perusahaan' => $companyId,
                    'kode' => $row->kode,
                    'nama' => $row->nama,
                    'kategori' => $row->kategori,
                    'default_unit' => $row->default_unit,
                ]);
                $stats['added']++;
            }
        }

        if ($mode === 'update') {
            $usedIds = IncomeEntry::where('id_perusahaan', $companyId)->pluck('id_income_type')->unique()->all();
            $orphans = $company->reject(fn ($r) => in_array(strtolower((string) $r->kode), $globalKodes, true))
                ->reject(fn ($r) => in_array($r->id_income_type, $usedIds, true));
            foreach ($orphans as $o) {
                $o->delete();
                $stats['deleted']++;
            }
        }

        return $stats;
    }

    private function syncUnits(int $companyId, string $mode): array
    {
        $stats = ['added' => 0, 'existing' => 0, 'updated' => 0, 'deleted' => 0];
        $global = Unit::where('id_perusahaan', null)->get();
        $globalNames = $global->map(fn ($r) => strtolower(trim((string) $r->nama)))->all();

        $company = Unit::withTrashed()->where('id_perusahaan', $companyId)->get();
        $map = $company->keyBy(fn ($r) => strtolower(trim((string) $r->nama)));

        foreach ($global as $row) {
            $key = strtolower(trim((string) $row->nama));
            $unit = $map->get($key);
            if ($unit) {
                if ($unit->trashed()) {
                    $unit->restore();
                    $stats['updated']++;
                    continue;
                }
                $stats['existing']++;
                if ($mode === 'update' && (string) $unit->simbol !== (string) $row->simbol) {
                    $unit->update(['simbol' => $row->simbol]);
                    $stats['updated']++;
                }
            } else {
                Unit::create([
                    'id_perusahaan' => $companyId,
                    'nama' => $row->nama,
                    'simbol' => $row->simbol,
                ]);
                $stats['added']++;
            }
        }

        if ($mode === 'update') {
            $usedNames = collect([
                CostEntry::where('id_perusahaan', $companyId)->pluck('unit'),
                IncomeEntry::where('id_perusahaan', $companyId)->pluck('unit'),
                CostType::where('id_perusahaan', $companyId)->pluck('default_unit'),
                IncomeType::where('id_perusahaan', $companyId)->pluck('default_unit'),
            ])->flatten()->filter()->map(fn ($n) => strtolower(trim((string) $n)))->unique()->all();

            $orphans = $company->reject(fn ($r) => $r->trashed())
                ->reject(fn ($r) => in_array(strtolower(trim((string) $r->nama)), $globalNames, true))
                ->reject(fn ($r) => in_array(strtolower(trim((string) $r->nama)), $usedNames, true));
            foreach ($orphans as $o) {
                $o->delete();
                $stats['deleted']++;
            }
        }

        return $stats;
    }

    private function categoryDirty($cat, $row): bool
    {
        foreach (['nama', 'icon', 'warna', 'urutan', 'is_active', 'kelompok'] as $f) {
            if ((string) $cat->{$f} !== (string) $row->{$f}) {
                return true;
            }
        }

        return false;
    }

    private function typeDirty($type, $row): bool
    {
        foreach (['nama', 'kategori', 'default_unit'] as $f) {
            if ((string) $type->{$f} !== (string) $row->{$f}) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensure a cost group exists for the company (copy from global or create placeholder).
     */
    private function ensureCostGroup(int $companyId, ?string $kode): void
    {
        $kode = strtolower(trim((string) $kode));
        if ($kode === '') {
            return;
        }
        if (CostGroup::where('id_perusahaan', $companyId)->where('kode', $kode)->exists()) {
            return;
        }

        $global = CostGroup::where('id_perusahaan', null)->where('kode', $kode)->first();
        $max = (int) CostGroup::where('id_perusahaan', $companyId)->max('urutan');

        CostGroup::create([
            'id_perusahaan' => $companyId,
            'kode' => $kode,
            'nama' => $global?->nama ?? ucfirst(str_replace('_', ' ', $kode)),
            'warna' => $global?->warna ?? 'gray',
            'urutan' => $global?->urutan ?? ($max + 1),
            'is_active' => true,
        ]);
    }

    /**
     * Ensure a cost category exists for the company (copy from global or create placeholder).
     */
    private function ensureCostCategory(int $companyId, ?string $kode): bool
    {
        $kode = strtolower(trim((string) $kode));
        if ($kode === '') {
            return false;
        }
        if (CostCategory::where('id_perusahaan', $companyId)->where('kode', $kode)->exists()) {
            return false;
        }

        $global = CostCategory::where('id_perusahaan', null)->where('kode', $kode)->first();
        $max = (int) CostCategory::where('id_perusahaan', $companyId)->max('urutan');

        CostCategory::create([
            'id_perusahaan' => $companyId,
            'kode' => $kode,
            'nama' => $global?->nama ?? ucfirst(str_replace('_', ' ', $kode)),
            'icon' => $global?->icon ?? 'bi-folder',
            'warna' => $global?->warna ?? 'gray',
            'urutan' => $global?->urutan ?? ($max + 1),
            'is_active' => $global?->is_active ?? true,
            'kelompok' => $global?->kelompok,
        ]);

        return true;
    }

    private function ensureIncomeCategory(int $companyId, ?string $kode): bool
    {
        $kode = strtolower(trim((string) $kode));
        if ($kode === '') {
            return false;
        }
        if (IncomeCategory::where('id_perusahaan', $companyId)->where('kode', $kode)->exists()) {
            return false;
        }

        $global = IncomeCategory::where('id_perusahaan', null)->where('kode', $kode)->first();
        $max = (int) IncomeCategory::where('id_perusahaan', $companyId)->max('urutan');

        IncomeCategory::create([
            'id_perusahaan' => $companyId,
            'kode' => $kode,
            'nama' => $global?->nama ?? ucfirst(str_replace('_', ' ', $kode)),
            'icon' => $global?->icon ?? 'bi-folder',
            'warna' => $global?->warna ?? 'green',
            'urutan' => $global?->urutan ?? ($max + 1),
            'is_active' => $global?->is_active ?? true,
        ]);

        return true;
    }

    /**
     * Ensure a unit exists for the company (restore trashed, copy from global, or create).
     */
    private function ensureUnit(int $companyId, ?string $nama): bool
    {
        $nama = trim((string) $nama);
        if ($nama === '') {
            return false;
        }

        $unit = Unit::withTrashed()->where('id_perusahaan', $companyId)->where('nama', $nama)->first();
        if ($unit) {
            if ($unit->trashed()) {
                $unit->restore();
            }

            return false;
        }

        $global = Unit::where('id_perusahaan', null)->where('nama', $nama)->first();
        Unit::create([
            'id_perusahaan' => $companyId,
            'nama' => $nama,
            'simbol' => $global?->simbol ?? '',
        ]);

        return true;
    }
}