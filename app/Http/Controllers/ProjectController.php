<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesDecimal;
use App\Models\Akun;
use App\Models\CostCategory;
use App\Models\CostEntry;
use App\Models\CostGroup;
use App\Models\CostType;
use App\Models\DailyClose;
use App\Models\FixedCost;
use App\Models\IncomeEntry;
use App\Models\IncomeType;
use App\Models\Pengguna;
use App\Models\Perusahaan;
use App\Models\Project;
use App\Models\ProjectAdmin;
use App\Models\ProjectCostPlan;
use App\Models\ProjectIncomePlan;
use App\Models\ProjectInvestor;
use App\Models\Unit;
use App\Services\BusinessTemplateSeeder;
use App\Services\CashService;
use App\Services\DailyControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    use HandlesDecimal;

    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;
        $statusFilter = $request->get('status');
        $modeFilter = $request->get('mode');

        $query = Project::with(['admins'])
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            });

        Perusahaan::filterByModule($query, $user->companyModule());

        if ($statusFilter === 'archive') {
            $query->where('status', 'archived');
        } else {
            $query->where('status', 'active');
        }

        if (in_array($modeFilter, [Project::MODE_PROJECT, Project::MODE_UMKM], true)) {
            $query->where('mode', $modeFilter);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        $countsBase = Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->when($statusFilter === 'archive', fn ($q) => $q->where('status', 'archived'), fn ($q) => $q->where('status', 'active'));

        Perusahaan::filterByModule($countsBase, $user->companyModule());

        $counts = [
            'all' => (clone $countsBase)->count(),
            'project' => (clone $countsBase)->where(function ($q) {
                $q->where('mode', Project::MODE_PROJECT)->orWhereNull('mode');
            })->count(),
            'umkm' => (clone $countsBase)->where('mode', Project::MODE_UMKM)->count(),
        ];

        return view('projects.index', [
            'title' => 'Unit Bisnis',
            'projects' => $projects,
            'statusFilter' => $statusFilter,
            'modeFilter' => $modeFilter,
            'counts' => $counts,
            'module' => $user->companyModule(),
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::with([
            'costEntries' => fn ($q) => $q->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc'),
            'costEntries.costType',
            'incomeEntries' => fn ($q) => $q->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc'),
            'incomeEntries.incomeType',
            'admins',
            'fixedCosts',
            'costPlans.costType',
            'incomePlans.incomeType',
        ])
            ->where('id_project', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        $costTypes = CostType::where('id_perusahaan', $companyId ?: null)->orderBy('kategori')->orderBy('nama')->get();
        $incomeTypes = IncomeType::where('id_perusahaan', $companyId ?: null)->orderBy('kategori')->orderBy('nama')->get();
        $costTypesByKategori = $costTypes->groupBy(fn ($t) => $t->kategori ?: 'Lainnya');
        $incomeTypesByKategori = $incomeTypes->groupBy(fn ($t) => $t->kategori ?: 'Lainnya');
        $units = Unit::where('deleted_at', null)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->orderBy('nama')
            ->get();

        $today = now()->format('Y-m-d');
        $todayCost = $project->costOnDate($today);
        $todayIncome = $project->incomeOnDate($today);
        $monthCost = $project->costInMonth();
        $monthIncome = $project->incomeInMonth();
        $dailyTarget = $project->budgetTargetForDate($today);
        $monthlyTarget = $project->monthlyBudgetTarget();

        $dailySnap = null;
        $recentDays = collect();
        $fixedCosts = collect();
        if ($project->isUmkm()) {
            $daily = app(DailyControlService::class);
            $dailySnap = $daily->snapshot($project, $today);
            $recentDays = $daily->recentDays($project, 7);
            $fixedCosts = $project->fixedCosts()->orderBy('nama')->get();
            $todayCost = $dailySnap['cost_cash'];
            $todayIncome = $dailySnap['income'];
        }

        $costPlans = $project->costPlans()->with('costType')->get();
        $incomePlans = $project->incomePlans()->with('incomeType')->get();
        $planCostTotal = (float) $costPlans->sum('amount');
        $planIncomeTotal = (float) $incomePlans->sum('amount');
        $actualCostByType = $project->costEntries()
            ->selectRaw('id_cost_type, SUM(total) as total')
            ->groupBy('id_cost_type')
            ->pluck('total', 'id_cost_type');
        $actualIncomeByType = $project->incomeEntries()
            ->selectRaw('id_income_type, SUM(total) as total')
            ->groupBy('id_income_type')
            ->pluck('total', 'id_income_type');

        $groupSummaries = [];
        if (! $project->isUmkm()) {
            $categoryKelompok = CostCategory::forCompany($companyId)->pluck('kelompok', 'kode');
            $groups = CostGroup::forCompany($companyId)->ordered()->get();
            $planByGroup = [];
            foreach ($costPlans as $plan) {
                $kel = $categoryKelompok[$plan->costType?->kategori] ?? null;
                if ($kel) $planByGroup[$kel] = ($planByGroup[$kel] ?? 0) + (float) $plan->amount;
            }
            $actualByGroup = [];
            foreach ($project->costEntries as $entry) {
                $kel = $entry->costType ? ($categoryKelompok[$entry->costType->kategori] ?? null) : null;
                if ($kel) $actualByGroup[$kel] = ($actualByGroup[$kel] ?? 0) + (float) $entry->total;
            }
            foreach ($groups as $group) {
                $kel = $group->kode;
                $plan = (float) ($planByGroup[$kel] ?? 0);
                $actual = (float) ($actualByGroup[$kel] ?? 0);
                if ($plan <= 0 && $actual <= 0) continue;
                $groupSummaries[] = [
                    'kelompok' => $kel,
                    'nama' => $group->nama,
                    'warna' => $group->warna,
                    'plan' => $plan,
                    'actual' => $actual,
                    'pct' => $plan > 0 ? round($actual / $plan * 100) : null,
                ];
            }
        }

        $availableAdmins = Pengguna::with('akun')
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->orderBy('nama_lengkap')
            ->get();
        $assignedAdminIds = $project->admins->pluck('id_pengguna')->all();
        $investor = ProjectInvestor::where('id_project', $project->id_project)
            ->with('akun.pengguna')
            ->first();

        $cash = app(CashService::class);
        $cashPosition = $cash->position($project);
        $cashSeries = $cash->series($project, now()->subDays(29), now());
        $cashForecast = $cash->forecast($project);

        return view('projects.show', [
            'title' => $project->isUmkm() ? 'Detail UMKM' : 'Detail Proyek',
            'project' => $project,
            'costTypes' => $costTypes,
            'incomeTypes' => $incomeTypes,
            'costTypesByKategori' => $costTypesByKategori,
            'incomeTypesByKategori' => $incomeTypesByKategori,
            'units' => $units,
            'isArchived' => $project->isArchived(),
            'todayCost' => $todayCost,
            'todayIncome' => $todayIncome,
            'todayMargin' => $todayIncome - $todayCost,
            'monthCost' => $monthCost,
            'monthIncome' => $monthIncome,
            'dailyTarget' => $dailyTarget,
            'monthlyTarget' => $monthlyTarget,
            'dailyUsagePct' => $project->budgetUsagePercent($todayCost, $dailyTarget),
            'monthlyUsagePct' => $monthlyTarget ? $project->budgetUsagePercent($monthCost, $monthlyTarget) : null,
            'dailySnap' => $dailySnap,
            'recentDays' => $recentDays,
            'fixedCosts' => $fixedCosts,
            'costPlans' => $costPlans,
            'incomePlans' => $incomePlans,
            'planCostTotal' => $planCostTotal,
            'planIncomeTotal' => $planIncomeTotal,
            'actualCostByType' => $actualCostByType,
            'actualIncomeByType' => $actualIncomeByType,
            'groupSummaries' => $groupSummaries,
            'availableAdmins' => $availableAdmins,
            'assignedAdminIds' => $assignedAdminIds,
            'investor' => $investor,
            'cashPosition' => $cashPosition,
            'cashSeries' => $cashSeries,
            'cashForecast' => $cashForecast,
        ]);
    }

    public function storeInvestor(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:akun,username',
        ]);

        $plainPassword = Str::random(12);

        try {
            DB::beginTransaction();

            $pengguna = Pengguna::create([
                'id_perusahaan' => $companyId,
                'nama_lengkap' => $request->nama_lengkap,
            ]);

            $akun = Akun::create([
                'id_pengguna' => $pengguna->id_pengguna,
                'username' => $request->username,
                'password' => $plainPassword,
                'role' => 'INVESTOR',
                'is_active' => '1',
            ]);

            ProjectInvestor::create([
                'id_project' => $project->id_project,
                'id_akun' => $akun->id_akun,
            ]);

            DB::commit();

            $prefix = str_contains(url()->previous(), '/projects/') ? 'projects' : 'cost-centers';

            return redirect()
                ->back()
                ->with('investor_created', [
                    'username' => $akun->username,
                    'password' => $plainPassword,
                    'nama_lengkap' => $pengguna->nama_lengkap,
                ])
                ->withFragment('investor');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['investor' => 'Gagal membuat akun investor: '.$e->getMessage()]);
        }
    }

    public function destroyInvestor(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $relation = ProjectInvestor::where('id_project', $project->id_project)->first();

        if ($relation) {
            try {
                DB::beginTransaction();
                $akun = Akun::find($relation->id_akun);
                $relation->delete();
                if ($akun) {
                    $akun->pengguna?->delete();
                    $akun->tokens()->delete();
                    $akun->delete();
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()->back()->withErrors(['investor' => 'Gagal menghapus investor: '.$e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', 'Akun investor berhasil dihapus.')->withFragment('investor');
    }

    public function resetInvestorPasswordWeb(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $relation = ProjectInvestor::where('id_project', $project->id_project)
            ->with('akun')
            ->first();

        if (! $relation || ! $relation->akun) {
            return redirect()->back()->withErrors(['investor' => 'Tidak ada investor pada proyek ini.']);
        }

        $plainPassword = Str::random(12);
        $relation->akun->update(['password' => $plainPassword]);
        $relation->akun->tokens()->delete();

        return redirect()
            ->back()
            ->with('investor_created', [
                'username' => $relation->akun->username,
                'password' => $plainPassword,
                'nama_lengkap' => $relation->akun->pengguna?->nama_lengkap,
            ])
            ->withFragment('investor');
    }

    public function toggleInvestor(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $relation = ProjectInvestor::where('id_project', $project->id_project)
            ->with('akun')
            ->first();

        if (! $relation || ! $relation->akun) {
            return redirect()->back()->withErrors(['investor' => 'Tidak ada investor pada proyek ini.']);
        }

        $akun = $relation->akun;
        $newStatus = $akun->is_active === '1' ? '0' : '1';
        $akun->update(['is_active' => $newStatus]);

        if ($newStatus === '0') {
            $akun->tokens()->delete();
        }

        $msg = $newStatus === '1'
            ? 'Akun investor berhasil diaktifkan.'
            : 'Akun investor berhasil dinonaktifkan. Investor tidak bisa login.';

        return redirect()->back()->with('success', $msg)->withFragment('investor');
    }

    public function syncAdmins(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $request->validate([
            'admin_ids' => 'nullable|array',
            'admin_ids.*' => 'integer|exists:pengguna,id_pengguna',
        ]);

        $ids = collect($request->admin_ids ?? [])
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        // Only allow users from same company
        $validIds = Pengguna::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->whereIn('id_pengguna', $ids)
            ->pluck('id_pengguna')
            ->all();

        // Replace assignments with company-aware pivot rows
        DB::transaction(function () use ($project, $companyId, $validIds) {
            ProjectAdmin::where('id_project', $project->id_project)->delete();
            foreach ($validIds as $penggunaId) {
                ProjectAdmin::create([
                    'id_project' => $project->id_project,
                    'id_pengguna' => $penggunaId,
                    'id_perusahaan' => $companyId,
                ]);
            }
        });

        return back()->with('success', 'Admin unit diperbarui.');
    }

    public function storeCostPlan(Request $request, $id)
    {
        return $this->upsertPlan($request, $id, 'cost');
    }

    public function updateCostPlan(Request $request, $id, $planId)
    {
        return $this->upsertPlan($request, $id, 'cost', $planId);
    }

    public function deleteCostPlan($id, $planId)
    {
        return $this->removePlan($id, $planId, 'cost');
    }

    public function storeIncomePlan(Request $request, $id)
    {
        return $this->upsertPlan($request, $id, 'income');
    }

    public function updateIncomePlan(Request $request, $id, $planId)
    {
        return $this->upsertPlan($request, $id, 'income', $planId);
    }

    public function deleteIncomePlan($id, $planId)
    {
        return $this->removePlan($id, $planId, 'income');
    }

    private function upsertPlan(Request $request, $id, string $kind, $planId = null)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Unit diarsipkan.');
        }

        if ($kind === 'cost') {
            $request->validate([
                'id_cost_type' => 'required|exists:cost_type,id_cost_type',
                'amount' => 'required|string',
            ]);
            $amount = $this->normalizeDecimal($request->amount, 0);
            $payload = [
                'id_perusahaan' => $companyId,
                'id_project' => $project->id_project,
                'id_cost_type' => $request->id_cost_type,
                'amount' => $amount,
            ];
            if ($planId) {
                ProjectCostPlan::where('id', $planId)->where('id_project', $id)->firstOrFail()->update($payload);
                $msg = 'Rencana biaya diperbarui.';
            } else {
                ProjectCostPlan::updateOrCreate(
                    [
                        'id_project' => $project->id_project,
                        'id_cost_type' => $request->id_cost_type,
                    ],
                    $payload
                );
                $msg = 'Rencana biaya ditambahkan.';
            }
        } else {
            $request->validate([
                'id_income_type' => 'required|exists:income_type,id_income_type',
                'amount' => 'required|string',
            ]);
            $amount = $this->normalizeDecimal($request->amount, 0);
            $payload = [
                'id_perusahaan' => $companyId,
                'id_project' => $project->id_project,
                'id_income_type' => $request->id_income_type,
                'amount' => $amount,
            ];
            if ($planId) {
                ProjectIncomePlan::where('id', $planId)->where('id_project', $id)->firstOrFail()->update($payload);
                $msg = 'Rencana pendapatan diperbarui.';
            } else {
                ProjectIncomePlan::updateOrCreate(
                    [
                        'id_project' => $project->id_project,
                        'id_income_type' => $request->id_income_type,
                    ],
                    $payload
                );
                $msg = 'Rencana pendapatan ditambahkan.';
            }
        }

        return redirect(route('projects.show', $id).'#plans')->with('success', $msg);
    }

    private function removePlan($id, $planId, string $kind)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($kind === 'cost') {
            ProjectCostPlan::where('id', $planId)->where('id_project', $id)->delete();
        } else {
            ProjectIncomePlan::where('id', $planId)->where('id_project', $id)->delete();
        }

        return redirect(route('projects.show', $id).'#plans')->with('success', 'Rencana dihapus.');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;
        $companyId = $user->id_perusahaan;

        $request->validate([
            'mode' => ['required', Rule::in([Project::MODE_PROJECT, Project::MODE_UMKM])],
            'nama_project' => 'required|string|max:255',
            'client' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'project_value' => 'nullable|string',
            'budget_period' => ['nullable', Rule::in([Project::BUDGET_TOTAL, Project::BUDGET_MONTHLY, Project::BUDGET_DAILY])],
            'daily_budget' => 'nullable|string',
            'monthly_budget' => 'nullable|string',
            'business_type' => 'nullable|string|max:50',
            'seed_template' => 'nullable|boolean',
            'generate_investor' => 'nullable|boolean',
            'opening_balance' => 'nullable|string',
        ]);

        $mode = $request->mode;
        $module = $user->companyModule();
        if ($module === Perusahaan::MODULE_PROJECT) {
            $mode = Project::MODE_PROJECT;
        } elseif ($module === Perusahaan::MODULE_UMKM) {
            $mode = Project::MODE_UMKM;
        }

        $budgetPeriod = $request->budget_period
            ?: ($mode === Project::MODE_UMKM ? Project::BUDGET_DAILY : Project::BUDGET_TOTAL);

        try {
            DB::beginTransaction();

            $project = Project::create([
                'id_perusahaan' => $companyId,
                'nama_project' => $request->nama_project,
                'client' => $mode === Project::MODE_UMKM ? ($request->client ?: $request->business_type) : $request->client,
                'lokasi' => $request->lokasi,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'project_value' => $request->project_value ? $this->normalizeDecimal($request->project_value) : null,
                'status' => 'active',
                'mode' => $mode,
                'budget_period' => $budgetPeriod,
                'daily_budget' => $request->daily_budget ? $this->normalizeDecimal($request->daily_budget) : null,
                'monthly_budget' => $request->monthly_budget ? $this->normalizeDecimal($request->monthly_budget) : null,
                'business_type' => $request->business_type,
                'opening_balance' => $request->opening_balance ? $this->normalizeDecimal($request->opening_balance) : null,
            ]);

            if ($mode === Project::MODE_UMKM && $request->boolean('seed_template', true)) {
                app(BusinessTemplateSeeder::class)->seedUmkm($companyId);
            }

            $investorCreds = null;
            if ($request->boolean('generate_investor')) {
                $investorCreds = $this->createInvestorForProject($project, $companyId);
            }

            DB::commit();

            $msg = $mode === Project::MODE_UMKM
                ? 'Unit UMKM berhasil dibuat. Siap catat omzet & biaya harian.'
                : 'Proyek berhasil ditambahkan. Silakan catat biaya/pendapatan.';

            $redirect = redirect()->route('projects.show', $project->id_project)
                ->with('success', $msg);

            if ($investorCreds) {
                $redirect->with('investor_created', $investorCreds)
                    ->withFragment('investor');
            }

            return $redirect;
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Gagal menyimpan unit: '.$e->getMessage());
        }
    }

    protected function createInvestorForProject(Project $project, int $companyId): ?array
    {
        $plainPassword = Str::random(12);
        $username = 'investor.'.$project->id_project;

        if (Akun::where('username', $username)->exists()) {
            return null;
        }

        $namaLengkap = 'Investor '.$project->nama_project;

        $pengguna = Pengguna::create([
            'id_perusahaan' => $companyId,
            'nama_lengkap' => $namaLengkap,
        ]);

        $akun = Akun::create([
            'id_pengguna' => $pengguna->id_pengguna,
            'username' => $username,
            'password' => $plainPassword,
            'role' => 'INVESTOR',
            'is_active' => '1',
        ]);

        ProjectInvestor::create([
            'id_project' => $project->id_project,
            'id_akun' => $akun->id_akun,
        ]);

        return [
            'username' => $akun->username,
            'password' => $plainPassword,
            'nama_lengkap' => $namaLengkap,
        ];
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Unit yang sudah diarsipkan tidak dapat diubah.');
        }

        $request->validate([
            'nama_project' => 'required|string|max:255',
            'client' => 'nullable|string|max:255',
            'lokasi' => 'nullable|string|max:255',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'project_value' => 'nullable|string',
            'budget_period' => ['nullable', Rule::in([Project::BUDGET_TOTAL, Project::BUDGET_MONTHLY, Project::BUDGET_DAILY])],
            'daily_budget' => 'nullable|string',
            'monthly_budget' => 'nullable|string',
            'business_type' => 'nullable|string|max:50',
            'cogs_ratio_alert' => 'nullable|numeric|min:0|max:100',
            'lock_closed_days' => 'nullable|boolean',
            'opening_balance' => 'nullable|string',
        ]);

        try {
            $data = [
                'nama_project' => $request->nama_project,
                'client' => $request->client,
                'lokasi' => $request->lokasi,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'project_value' => $request->project_value ? $this->normalizeDecimal($request->project_value) : null,
                'budget_period' => $request->budget_period ?: $project->budget_period,
                'daily_budget' => $request->daily_budget !== null && $request->daily_budget !== ''
                    ? $this->normalizeDecimal($request->daily_budget)
                    : $project->daily_budget,
                'monthly_budget' => $request->monthly_budget !== null && $request->monthly_budget !== ''
                    ? $this->normalizeDecimal($request->monthly_budget)
                    : $project->monthly_budget,
                'business_type' => $request->business_type,
                'opening_balance' => $request->opening_balance !== null && $request->opening_balance !== ''
                    ? $this->normalizeDecimal($request->opening_balance)
                    : $project->opening_balance,
            ];

            if ($request->has('cogs_ratio_alert')) {
                $pct = $request->cogs_ratio_alert;
                $data['cogs_ratio_alert'] = ($pct === null || $pct === '')
                    ? null
                    : ((float) $pct / 100);
            }
            if ($request->has('lock_closed_days')) {
                $data['lock_closed_days'] = $request->boolean('lock_closed_days');
            }

            // Allow clearing money fields when explicitly empty string submitted
            if ($request->has('daily_budget') && $request->daily_budget === '') {
                $data['daily_budget'] = null;
            }
            if ($request->has('monthly_budget') && $request->monthly_budget === '') {
                $data['monthly_budget'] = null;
            }
            if ($request->has('project_value') && $request->project_value === '') {
                $data['project_value'] = null;
            }
            if ($request->has('opening_balance') && $request->opening_balance === '') {
                $data['opening_balance'] = null;
            }

            $project->update($data);

            return redirect()->route('projects.show', $id)
                ->with('success', 'Unit bisnis berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal mengubah unit: '.$e->getMessage());
        }
    }

    public function addCost(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Project sudah diarsipkan. Tidak bisa menambah biaya.');
        }

        $request->validate([
            'id_cost_type' => 'required|exists:cost_type,id_cost_type',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'harga_satuan' => 'nullable|string',
            'total' => 'nullable|string',
            'catatan' => 'nullable|string',
            'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        if ($msg = $this->guardClosedDay($project, $request->tanggal)) {
            return back()->withInput()->with('error', $msg);
        }

        try {
            $qty = $this->normalizeDecimal($request->qty);
            $hargaSatuan = $this->normalizeMoney($request->harga_satuan);
            $total = $request->total ? $this->normalizeMoney($request->total) : ($qty * $hargaSatuan);

            $data = [
                'id_perusahaan' => $companyId,
                'id_project' => $id,
                'id_cost_type' => $request->id_cost_type,
                'tanggal' => $request->tanggal,
                'keterangan' => $request->keterangan,
                'qty' => $qty,
                'unit' => $request->unit,
                'harga_satuan' => $hargaSatuan,
                'total' => $total,
                'catatan' => $request->catatan,
            ];

            if ($request->hasFile('file_bukti')) {
                $file = $request->file('file_bukti');
                $filename = 'cost_bukti_'.time().'_'.bin2hex(random_bytes(4)).'.webp';
                $file->storeAs('bukti/cost', $filename, 'public');
                $data['file_bukti'] = $filename;
            }

            CostEntry::create($data);

            return redirect(route('projects.show', $id).'#costs')
                ->with('success', 'Biaya berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menambah biaya: '.$e->getMessage());
        }
    }

    public function updateCost(Request $request, $id, $costId)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Unit diarsipkan. Tidak bisa mengubah entri.');
        }

        $cost = CostEntry::where('id_cost', $costId)->where('id_project', $id)->firstOrFail();

        $request->validate([
            'id_cost_type' => 'required|exists:cost_type,id_cost_type',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'harga_satuan' => 'nullable|string',
            'total' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        if ($msg = $this->guardClosedDay($project, $request->tanggal)) {
            return back()->withInput()->with('error', $msg);
        }

        $qty = $this->normalizeDecimal($request->qty);
        $hargaSatuan = $this->normalizeMoney($request->harga_satuan);
        $total = $request->total !== null && $request->total !== ''
            ? $this->normalizeMoney($request->total)
            : ($qty * $hargaSatuan);

        $data = [
            'id_cost_type' => $request->id_cost_type,
            'tanggal' => $request->tanggal,
            'keterangan' => $request->keterangan,
            'qty' => $qty,
            'unit' => $request->unit,
            'harga_satuan' => $hargaSatuan,
            'total' => $total,
            'catatan' => $request->catatan,
        ];

        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            if ($file->isValid() && $file->getSize() <= 3 * 1024 * 1024) {
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file->getMimeType(), $allowedMime)) {
                    $filename = 'cost_bukti_'.time().'_'.bin2hex(random_bytes(4)).'.webp';
                    $file->storeAs('bukti/cost', $filename, 'public');
                    $data['file_bukti'] = $filename;
                }
            }
        }

        $cost->update($data);

        return redirect(route('projects.show', $id).'#costs')->with('success', 'Entri biaya diperbarui.');
    }

    public function addIncome(Request $request, $id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Project sudah diarsipkan. Tidak bisa menambah pendapatan.');
        }

        $request->validate([
            'id_income_type' => 'required|exists:income_type,id_income_type',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'harga_satuan' => 'nullable|string',
            'total' => 'nullable|string',
            'catatan' => 'nullable|string',
            'file_bukti' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        if ($msg = $this->guardClosedDay($project, $request->tanggal)) {
            return back()->withInput()->with('error', $msg);
        }

        try {
            $qty = $this->normalizeDecimal($request->qty);
            $hargaSatuan = $this->normalizeMoney($request->harga_satuan);
            $total = $request->total ? $this->normalizeMoney($request->total) : ($qty * $hargaSatuan);

            $data = [
                'id_perusahaan' => $companyId,
                'id_project' => $id,
                'id_income_type' => $request->id_income_type,
                'tanggal' => $request->tanggal,
                'keterangan' => $request->keterangan,
                'qty' => $qty,
                'unit' => $request->unit,
                'harga_satuan' => $hargaSatuan,
                'total' => $total,
                'catatan' => $request->catatan,
            ];

            if ($request->hasFile('file_bukti')) {
                $file = $request->file('file_bukti');
                $filename = 'income_bukti_'.time().'_'.bin2hex(random_bytes(4)).'.webp';
                $file->storeAs('bukti/income', $filename, 'public');
                $data['file_bukti'] = $filename;
            }

            IncomeEntry::create($data);

            return redirect(route('projects.show', $id).'#incomes')
                ->with('success', 'Pendapatan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menambah pendapatan: '.$e->getMessage());
        }
    }

    public function updateIncome(Request $request, $id, $incomeId)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Unit diarsipkan. Tidak bisa mengubah entri.');
        }

        $income = IncomeEntry::where('id_income', $incomeId)->where('id_project', $id)->firstOrFail();

        $request->validate([
            'id_income_type' => 'required|exists:income_type,id_income_type',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string|max:50',
            'harga_satuan' => 'nullable|string',
            'total' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        if ($msg = $this->guardClosedDay($project, $request->tanggal)) {
            return back()->withInput()->with('error', $msg);
        }

        $qty = $this->normalizeDecimal($request->qty);
        $hargaSatuan = $this->normalizeMoney($request->harga_satuan);
        $total = $request->total !== null && $request->total !== ''
            ? $this->normalizeMoney($request->total)
            : ($qty * $hargaSatuan);

        $data = [
            'id_income_type' => $request->id_income_type,
            'tanggal' => $request->tanggal,
            'keterangan' => $request->keterangan,
            'qty' => $qty,
            'unit' => $request->unit,
            'harga_satuan' => $hargaSatuan,
            'total' => $total,
            'catatan' => $request->catatan,
        ];

        if ($request->hasFile('file_bukti')) {
            $file = $request->file('file_bukti');
            if ($file->isValid() && $file->getSize() <= 3 * 1024 * 1024) {
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file->getMimeType(), $allowedMime)) {
                    $filename = 'income_bukti_'.time().'_'.bin2hex(random_bytes(4)).'.webp';
                    $file->storeAs('bukti/income', $filename, 'public');
                    $data['file_bukti'] = $filename;
                }
            }
        }

        $income->update($data);

        return redirect(route('projects.show', $id).'#incomes')->with('success', 'Entri pendapatan diperbarui.');
    }

    public function deleteCost($id, $costId)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Project sudah diarsipkan. Riwayat tidak dapat dihapus.');
        }

        $cost = CostEntry::where('id_cost', $costId)
            ->where('id_project', $id)
            ->firstOrFail();

        if ($msg = $this->guardClosedDay($project, $cost->tanggal)) {
            return back()->with('error', $msg);
        }

        $cost->delete();

        return redirect(route('projects.show', $id).'#costs')
            ->with('success', 'Riwayat biaya berhasil dihapus.');
    }

    public function deleteIncome($id, $incomeId)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Project sudah diarsipkan. Riwayat tidak dapat dihapus.');
        }

        $income = IncomeEntry::where('id_income', $incomeId)
            ->where('id_project', $id)
            ->firstOrFail();

        if ($msg = $this->guardClosedDay($project, $income->tanggal)) {
            return back()->with('error', $msg);
        }

        $income->delete();

        return redirect(route('projects.show', $id).'#incomes')
            ->with('success', 'Riwayat pendapatan berhasil dihapus.');
    }

    public function archive($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        $newStatus = $project->isArchived() ? 'active' : 'archived';
        $project->update(['status' => $newStatus]);

        $msg = $newStatus === 'archived'
            ? 'Project dipindahkan ke arsip.'
            : 'Project diaktifkan kembali.';

        return redirect()->route('projects.index')->with('success', $msg);
    }

    public function delete($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        if ($project->isArchived()) {
            return back()->with('error', 'Project yang sudah diarsipkan tidak dapat dihapus.');
        }

        try {
            DB::beginTransaction();

            CostEntry::where('id_project', $id)->delete();
            IncomeEntry::where('id_project', $id)->delete();
            ProjectAdmin::where('id_project', $id)->delete();
            ProjectCostPlan::where('id_project', $id)->delete();
            ProjectIncomePlan::where('id_project', $id)->delete();
            FixedCost::where('id_project', $id)->delete();
            DailyClose::where('id_project', $id)->delete();
            $project->delete();

            DB::commit();

            return redirect()->route('projects.index')
                ->with('success', 'Project dan seluruh riwayat biaya/pendapatan telah dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus project: '.$e->getMessage());
        }
    }

    public function costBukti($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $cost = CostEntry::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->findOrFail($id);

        if (! $cost->file_bukti) {
            abort(404, 'Bukti tidak ditemukan');
        }

        $path = storage_path('app/public/bukti/cost/'.$cost->file_bukti);

        if (! file_exists($path)) {
            abort(404, 'Bukti tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function incomeBukti($id)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        $income = IncomeEntry::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->findOrFail($id);

        if (! $income->file_bukti) {
            abort(404, 'Bukti tidak ditemukan');
        }

        $path = storage_path('app/public/bukti/income/'.$income->file_bukti);

        if (! file_exists($path)) {
            abort(404, 'Bukti tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
