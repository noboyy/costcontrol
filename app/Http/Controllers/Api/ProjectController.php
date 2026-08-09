<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesDecimal;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\CostEntry;
use App\Models\CostType;
use App\Models\DailyClose;
use App\Models\FixedCost;
use App\Models\IncomeEntry;
use App\Models\IncomeType;
use App\Models\Akun;
use App\Models\Pengguna;
use App\Models\Project;
use App\Models\ProjectAdmin;
use App\Models\ProjectCostPlan;
use App\Models\ProjectIncomePlan;
use App\Models\ProjectInvestor;
use App\Models\Unit;
use App\Services\BusinessTemplateSeeder;
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
        $user = $request->user();
        $companyId = $user->id_perusahaan;
        $statusFilter = $request->get('status');
        $modeFilter = $request->get('mode');

        $query = Project::with(['admins'])
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId));

        if ($statusFilter === 'archive') {
            $query->where('status', 'archived');
        } else {
            $query->where('status', 'active');
        }

        if (in_array($modeFilter, [Project::MODE_PROJECT, Project::MODE_UMKM], true)) {
            $query->where('mode', $modeFilter);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        $counts = [
            'all' => Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
                ->when($statusFilter === 'archive', fn ($q) => $q->where('status', 'archived'), fn ($q) => $q->where('status', 'active'))
                ->count(),
            'project' => Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
                ->when($statusFilter === 'archive', fn ($q) => $q->where('status', 'archived'), fn ($q) => $q->where('status', 'active'))
                ->where(fn ($q) => $q->where('mode', Project::MODE_PROJECT)->orWhereNull('mode'))
                ->count(),
            'umkm' => Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
                ->when($statusFilter === 'archive', fn ($q) => $q->where('status', 'archived'), fn ($q) => $q->where('status', 'active'))
                ->where('mode', Project::MODE_UMKM)
                ->count(),
        ];

        return response()->json([
            'projects' => ProjectResource::collection($projects),
            'counts' => $counts,
            'statusFilter' => $statusFilter,
            'modeFilter' => $modeFilter,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::with([
            'costEntries.costType',
            'incomeEntries.incomeType',
            'admins',
            'fixedCosts',
            'costPlans.costType',
            'incomePlans.incomeType',
        ])
            ->where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $costTypes = CostType::where('id_perusahaan', $companyId ?: null)->orderBy('nama')->get();
        $incomeTypes = IncomeType::where('id_perusahaan', $companyId ?: null)->orderBy('nama')->get();
        $units = Unit::where('deleted_at', null)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
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

        $availableAdmins = Pengguna::with('akun')
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->orderBy('nama_lengkap')
            ->get();
        $assignedAdminIds = $project->admins->pluck('id_pengguna')->all();

        return response()->json([
            'project' => new ProjectResource($project),
            'costTypes' => $costTypes,
            'incomeTypes' => $incomeTypes,
            'units' => $units,
            'isArchived' => $project->isArchived(),
            'summaries' => [
                'todayCost' => $todayCost,
                'todayIncome' => $todayIncome,
                'todayMargin' => $todayIncome - $todayCost,
                'monthCost' => $monthCost,
                'monthIncome' => $monthIncome,
                'dailyTarget' => $dailyTarget,
                'monthlyTarget' => $monthlyTarget,
                'dailyUsagePct' => $project->budgetUsagePercent($todayCost, $dailyTarget),
                'monthlyUsagePct' => $monthlyTarget ? $project->budgetUsagePercent($monthCost, $monthlyTarget) : null,
            ],
            'plans' => [
                'cost' => $costPlans->map(fn ($p) => [
                    'id' => $p->id,
                    'id_cost_type' => $p->id_cost_type,
                    'nama' => $p->costType?->nama,
                    'amount' => (float) $p->amount,
                    'actual' => (float) ($actualCostByType[$p->id_cost_type] ?? 0),
                ]),
                'income' => $incomePlans->map(fn ($p) => [
                    'id' => $p->id,
                    'id_income_type' => $p->id_income_type,
                    'nama' => $p->incomeType?->nama,
                    'amount' => (float) $p->amount,
                    'actual' => (float) ($actualIncomeByType[$p->id_income_type] ?? 0),
                ]),
                'costTotal' => $planCostTotal,
                'incomeTotal' => $planIncomeTotal,
            ],
            'dailySnap' => $dailySnap,
            'recentDays' => $recentDays,
            'fixedCosts' => $fixedCosts,
            'availableAdmins' => $availableAdmins,
            'assignedAdminIds' => $assignedAdminIds,
            'costEntries' => $project->costEntries->map(fn ($e) => [
                'id' => $e->id_cost,
                'tanggal' => $e->tanggal,
                'keterangan' => $e->keterangan,
                'total' => (float) $e->total,
                'tipe' => $e->costType?->nama,
            ]),
            'incomeEntries' => $project->incomeEntries->map(fn ($e) => [
                'id' => $e->id_income,
                'tanggal' => $e->tanggal,
                'keterangan' => $e->keterangan,
                'total' => (float) $e->total,
                'tipe' => $e->incomeType?->nama,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
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
        ]);

        $mode = $request->mode;
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
            ]);

            if ($mode === Project::MODE_UMKM && $request->boolean('seed_template', true)) {
                app(BusinessTemplateSeeder::class)->seedUmkm($companyId);
            }

            DB::commit();

            return response()->json([
                'message' => 'Unit berhasil dibuat.',
                'project' => new ProjectResource($project),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal menyimpan unit: '.$e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Unit yang sudah diarsipkan tidak dapat diubah.'], 422);
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
        ]);

        $data = [
            'nama_project' => $request->nama_project,
            'client' => $request->client,
            'lokasi' => $request->lokasi,
            'date_start' => $request->date_start,
            'date_end' => $request->date_end,
            'project_value' => $request->has('project_value') && $request->project_value !== '' ? $this->normalizeDecimal($request->project_value) : $project->project_value,
            'budget_period' => $request->budget_period ?: $project->budget_period,
            'daily_budget' => $request->has('daily_budget') && $request->daily_budget !== '' ? $this->normalizeDecimal($request->daily_budget) : $project->daily_budget,
            'monthly_budget' => $request->has('monthly_budget') && $request->monthly_budget !== '' ? $this->normalizeDecimal($request->monthly_budget) : $project->monthly_budget,
            'business_type' => $request->business_type,
        ];

        if ($request->has('cogs_ratio_alert')) {
            $data['cogs_ratio_alert'] = ($request->cogs_ratio_alert === null || $request->cogs_ratio_alert === '')
                ? null : ((float) $request->cogs_ratio_alert / 100);
        }
        if ($request->has('lock_closed_days')) {
            $data['lock_closed_days'] = $request->boolean('lock_closed_days');
        }

        $project->update($data);

        return response()->json([
            'message' => 'Unit bisnis berhasil diperbarui.',
            'project' => new ProjectResource($project),
        ]);
    }

    public function addCost(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Project sudah diarsipkan.'], 422);
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
        ]);

        if ($msg = $this->guardClosedDay($project, $request->tanggal)) {
            return response()->json(['message' => $msg], 422);
        }

        $qty = $this->normalizeDecimal($request->qty);
        $hargaSatuan = $this->normalizeDecimal($request->harga_satuan);
        $total = $request->total ? $this->normalizeDecimal($request->total) : ($qty * $hargaSatuan);

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
            $data['file_bukti'] = $this->storeBukti($request, 'cost');
        }

        CostEntry::create($data);

        return response()->json(['message' => 'Biaya berhasil ditambahkan.'], 201);
    }

    public function updateCost(Request $request, $id, $costId)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Unit diarsipkan.'], 422);
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
            return response()->json(['message' => $msg], 422);
        }

        $qty = $this->normalizeDecimal($request->qty);
        $hargaSatuan = $this->normalizeDecimal($request->harga_satuan, 0);
        $total = $request->total !== null && $request->total !== ''
            ? $this->normalizeDecimal($request->total)
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
            $data['file_bukti'] = $this->storeBukti($request, 'cost');
        }

        $cost->update($data);

        return response()->json(['message' => 'Entri biaya diperbarui.']);
    }

    public function deleteCost(Request $request, $id, $costId)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Project sudah diarsipkan.'], 422);
        }

        $cost = CostEntry::where('id_cost', $costId)->where('id_project', $id)->firstOrFail();

        if ($msg = $this->guardClosedDay($project, $cost->tanggal)) {
            return response()->json(['message' => $msg], 422);
        }

        $cost->delete();

        return response()->json(['message' => 'Riwayat biaya berhasil dihapus.']);
    }

    public function addIncome(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Project sudah diarsipkan.'], 422);
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
        ]);

        if ($msg = $this->guardClosedDay($project, $request->tanggal)) {
            return response()->json(['message' => $msg], 422);
        }

        $qty = $this->normalizeDecimal($request->qty);
        $hargaSatuan = $this->normalizeDecimal($request->harga_satuan);
        $total = $request->total ? $this->normalizeDecimal($request->total) : ($qty * $hargaSatuan);

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
            $data['file_bukti'] = $this->storeBukti($request, 'income');
        }

        IncomeEntry::create($data);

        return response()->json(['message' => 'Pendapatan berhasil ditambahkan.'], 201);
    }

    public function updateIncome(Request $request, $id, $incomeId)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Unit diarsipkan.'], 422);
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
            return response()->json(['message' => $msg], 422);
        }

        $qty = $this->normalizeDecimal($request->qty);
        $hargaSatuan = $this->normalizeDecimal($request->harga_satuan, 0);
        $total = $request->total !== null && $request->total !== ''
            ? $this->normalizeDecimal($request->total)
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
            $data['file_bukti'] = $this->storeBukti($request, 'income');
        }

        $income->update($data);

        return response()->json(['message' => 'Entri pendapatan diperbarui.']);
    }

    public function deleteIncome(Request $request, $id, $incomeId)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Project sudah diarsipkan.'], 422);
        }

        $income = IncomeEntry::where('id_income', $incomeId)->where('id_project', $id)->firstOrFail();

        if ($msg = $this->guardClosedDay($project, $income->tanggal)) {
            return response()->json(['message' => $msg], 422);
        }

        $income->delete();

        return response()->json(['message' => 'Riwayat pendapatan berhasil dihapus.']);
    }

    public function syncAdmins(Request $request, $id)
    {
        $user = $request->user();
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

        $validIds = Pengguna::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->whereIn('id_pengguna', $ids)
            ->pluck('id_pengguna')
            ->all();

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

        return response()->json(['message' => 'Admin unit diperbarui.']);
    }

    public function upsertPlan(Request $request, $id, string $kind, $planId = null)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Unit diarsipkan.'], 422);
        }

        if ($kind === 'cost') {
            $request->validate([
                'id_cost_type' => 'required|exists:cost_type,id_cost_type',
                'amount' => 'required|string',
            ]);
            $payload = [
                'id_perusahaan' => $companyId,
                'id_project' => $project->id_project,
                'id_cost_type' => $request->id_cost_type,
                'amount' => $this->normalizeDecimal($request->amount, 0),
            ];
            if ($planId) {
                ProjectCostPlan::where('id', $planId)->where('id_project', $id)->firstOrFail()->update($payload);
                $msg = 'Rencana biaya diperbarui.';
            } else {
                ProjectCostPlan::updateOrCreate(
                    ['id_project' => $project->id_project, 'id_cost_type' => $request->id_cost_type],
                    $payload
                );
                $msg = 'Rencana biaya ditambahkan.';
            }
        } else {
            $request->validate([
                'id_income_type' => 'required|exists:income_type,id_income_type',
                'amount' => 'required|string',
            ]);
            $payload = [
                'id_perusahaan' => $companyId,
                'id_project' => $project->id_project,
                'id_income_type' => $request->id_income_type,
                'amount' => $this->normalizeDecimal($request->amount, 0),
            ];
            if ($planId) {
                ProjectIncomePlan::where('id', $planId)->where('id_project', $id)->firstOrFail()->update($payload);
                $msg = 'Rencana pendapatan diperbarui.';
            } else {
                ProjectIncomePlan::updateOrCreate(
                    ['id_project' => $project->id_project, 'id_income_type' => $request->id_income_type],
                    $payload
                );
                $msg = 'Rencana pendapatan ditambahkan.';
            }
        }

        return response()->json(['message' => $msg]);
    }

    public function deletePlan(Request $request, $id, $planId, string $kind)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($kind === 'cost') {
            ProjectCostPlan::where('id', $planId)->where('id_project', $id)->delete();
        } else {
            ProjectIncomePlan::where('id', $planId)->where('id_project', $id)->delete();
        }

        return response()->json(['message' => 'Rencana dihapus.']);
    }

    public function archive(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $newStatus = $project->isArchived() ? 'active' : 'archived';
        $project->update(['status' => $newStatus]);

        return response()->json([
            'message' => $newStatus === 'archived' ? 'Project dipindahkan ke arsip.' : 'Project diaktifkan kembali.',
            'status' => $newStatus,
        ]);
    }

    public function delete(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($project->isArchived()) {
            return response()->json(['message' => 'Project yang sudah diarsipkan tidak dapat dihapus.'], 422);
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

            // Hapus akun & pengguna investor sebelum relasi
            $investorRelations = ProjectInvestor::where('id_project', $id)->get();
            foreach ($investorRelations as $relation) {
                $akun = Akun::find($relation->id_akun);
                if ($akun) {
                    $akun->pengguna?->delete();
                    $akun->tokens()->delete();
                    $akun->delete();
                }
            }
            ProjectInvestor::where('id_project', $id)->delete();

            $project->delete();
            DB::commit();

            return response()->json(['message' => 'Project dan riwayat biaya/pendapatan telah dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal menghapus project: '.$e->getMessage()], 500);
        }
    }

    public function assignInvestor(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:akun,username',
            'password' => 'nullable|string|min:8',
        ]);

        $plainPassword = $request->password ?? Str::random(12);

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

            return response()->json([
                'message' => 'Akun investor berhasil dibuat.',
                'username' => $akun->username,
                'password' => $plainPassword,
                'nama_lengkap' => $pengguna->nama_lengkap,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal membuat akun investor: '.$e->getMessage()], 500);
        }
    }

    public function revokeInvestor(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $relation = ProjectInvestor::where('id_project', $project->id_project)->first();

        if (! $relation) {
            return response()->json(['message' => 'Tidak ada investor pada proyek ini.'], 404);
        }

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

            return response()->json(['message' => 'Akun investor berhasil dicabut.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal mencabut investor: '.$e->getMessage()], 500);
        }
    }

    public function resetInvestorPassword(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $relation = ProjectInvestor::where('id_project', $project->id_project)
            ->with('akun')
            ->first();

        if (! $relation || ! $relation->akun) {
            return response()->json(['message' => 'Tidak ada investor pada proyek ini.'], 404);
        }

        $plainPassword = Str::random(12);
        $relation->akun->update(['password' => $plainPassword]);
        $relation->akun->tokens()->delete();

        return response()->json([
            'message' => 'Password investor berhasil direset.',
            'username' => $relation->akun->username,
            'password' => $plainPassword,
        ]);
    }

    public function showInvestor(Request $request, $id)
    {
        $user = $request->user();
        $companyId = $user->id_perusahaan;

        $project = Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        $relation = ProjectInvestor::where('id_project', $project->id_project)
            ->with('akun.pengguna')
            ->first();

        if (! $relation) {
            return response()->json(['investor' => null]);
        }

        return response()->json([
            'investor' => [
                'id_akun' => $relation->akun->id_akun,
                'username' => $relation->akun->username,
                'nama_lengkap' => $relation->akun->pengguna?->nama_lengkap,
                'is_active' => $relation->akun->is_active,
            ],
        ]);
    }

    private function storeBukti(Request $request, string $kind): ?string
    {
        $file = $request->file('file_bukti');
        if (! $file || ! $file->isValid() || $file->getSize() > 3 * 1024 * 1024) {
            return null;
        }
        $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($file->getMimeType(), $allowedMime)) {
            return null;
        }
        $filename = $kind.'_bukti_'.time().'_'.bin2hex(random_bytes(4)).'.webp';
        $file->storeAs('bukti/'.$kind, $filename, 'public');

        return $filename;
    }
}
