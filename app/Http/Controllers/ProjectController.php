<?php

namespace App\Http\Controllers;

use App\Models\CostEntry;
use App\Models\CostType;
use App\Models\FixedCost;
use App\Models\IncomeEntry;
use App\Models\IncomeType;
use App\Models\Project;
use App\Models\ProjectAdmin;
use App\Models\Unit;
use App\Services\BusinessTemplateSeeder;
use App\Services\DailyControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
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
                ->where(function ($q) {
                    $q->where('mode', Project::MODE_PROJECT)->orWhereNull('mode');
                })->count(),
            'umkm' => Project::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
                ->when($statusFilter === 'archive', fn ($q) => $q->where('status', 'archived'), fn ($q) => $q->where('status', 'active'))
                ->where('mode', Project::MODE_UMKM)->count(),
        ];

        return view('projects.index', [
            'title' => 'Unit Bisnis',
            'projects' => $projects,
            'statusFilter' => $statusFilter,
            'modeFilter' => $modeFilter,
            'counts' => $counts,
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();
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
            ->when($companyId, function ($q) use ($companyId) {
                $q->where('id_perusahaan', $companyId);
            })
            ->firstOrFail();

        $costTypes = CostType::where('id_perusahaan', $companyId ?: null)->orderBy('nama')->get();
        $incomeTypes = IncomeType::where('id_perusahaan', $companyId ?: null)->orderBy('nama')->get();
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

        $availableAdmins = \App\Models\Pengguna::with('akun')
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->orderBy('nama_lengkap')
            ->get();
        $assignedAdminIds = $project->admins->pluck('id_pengguna')->all();

        return view('projects.show', [
            'title' => $project->isUmkm() ? 'Detail UMKM' : 'Detail Proyek',
            'project' => $project,
            'costTypes' => $costTypes,
            'incomeTypes' => $incomeTypes,
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
            'availableAdmins' => $availableAdmins,
            'assignedAdminIds' => $assignedAdminIds,
        ]);
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
        $validIds = \App\Models\Pengguna::when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->whereIn('id_pengguna', $ids)
            ->pluck('id_pengguna')
            ->all();

        // Replace assignments with company-aware pivot rows
        ProjectAdmin::where('id_project', $project->id_project)->delete();
        foreach ($validIds as $penggunaId) {
            ProjectAdmin::create([
                'id_project' => $project->id_project,
                'id_pengguna' => $penggunaId,
                'id_perusahaan' => $companyId,
            ]);
        }

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
                \App\Models\ProjectCostPlan::where('id', $planId)->where('id_project', $id)->firstOrFail()->update($payload);
                $msg = 'Rencana biaya diperbarui.';
            } else {
                \App\Models\ProjectCostPlan::updateOrCreate(
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
                \App\Models\ProjectIncomePlan::where('id', $planId)->where('id_project', $id)->firstOrFail()->update($payload);
                $msg = 'Rencana pendapatan diperbarui.';
            } else {
                \App\Models\ProjectIncomePlan::updateOrCreate(
                    [
                        'id_project' => $project->id_project,
                        'id_income_type' => $request->id_income_type,
                    ],
                    $payload
                );
                $msg = 'Rencana pendapatan ditambahkan.';
            }
        }

        return back()->with('success', $msg);
    }

    private function removePlan($id, $planId, string $kind)
    {
        $user = auth()->user();
        $companyId = $user->id_perusahaan;

        Project::where('id_project', $id)
            ->when($companyId, fn ($q) => $q->where('id_perusahaan', $companyId))
            ->firstOrFail();

        if ($kind === 'cost') {
            \App\Models\ProjectCostPlan::where('id', $planId)->where('id_project', $id)->delete();
        } else {
            \App\Models\ProjectIncomePlan::where('id', $planId)->where('id_project', $id)->delete();
        }

        return back()->with('success', 'Rencana dihapus.');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
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

            $msg = $mode === Project::MODE_UMKM
                ? 'Unit UMKM berhasil dibuat. Siap catat omzet & biaya harian.'
                : 'Proyek berhasil ditambahkan. Silakan catat biaya/pendapatan.';

            return redirect()->route('projects.show', $project->id_project)
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Gagal menyimpan unit: ' . $e->getMessage());
        }
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

            $project->update($data);

            return redirect()->route('projects.show', $id)
                ->with('success', 'Unit bisnis berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal mengubah unit: ' . $e->getMessage());
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
            'harga_satuan' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($msg = $this->guardClosedDay($project, $request->tanggal)) {
            return back()->withInput()->with('error', $msg);
        }

        try {
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

            // Handle file upload
            if ($request->hasFile('file_bukti')) {
                $file = $request->file('file_bukti');
                if ($file->isValid() && $file->getSize() <= 3 * 1024 * 1024) {
                    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
                    if (in_array($file->getMimeType(), $allowedMime)) {
                        $filename = 'cost_bukti_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
                        $path = $file->storeAs('bukti/cost', $filename, 'public');
                        $data['file_bukti'] = $filename;
                    }
                }
            }

            CostEntry::create($data);

            return redirect()->route('projects.show', $id)
                ->with('success', 'Biaya berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menambah biaya: ' . $e->getMessage());
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

        if ($msg = $this->guardClosedDay($project, $cost->tanggal)) {
            return back()->with('error', $msg);
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
            return back()->withInput()->with('error', $msg);
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
            $file = $request->file('file_bukti');
            if ($file->isValid() && $file->getSize() <= 3 * 1024 * 1024) {
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file->getMimeType(), $allowedMime)) {
                    $filename = 'cost_bukti_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
                    $file->storeAs('bukti/cost', $filename, 'public');
                    $data['file_bukti'] = $filename;
                }
            }
        }

        $cost->update($data);

        return redirect()->route('projects.show', $id)->with('success', 'Entri biaya diperbarui.');
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
            'harga_satuan' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($msg = $this->guardClosedDay($project, $request->tanggal)) {
            return back()->withInput()->with('error', $msg);
        }

        try {
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

            // Handle file upload
            if ($request->hasFile('file_bukti')) {
                $file = $request->file('file_bukti');
                if ($file->isValid() && $file->getSize() <= 3 * 1024 * 1024) {
                    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
                    if (in_array($file->getMimeType(), $allowedMime)) {
                        $filename = 'income_bukti_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
                        $path = $file->storeAs('bukti/income', $filename, 'public');
                        $data['file_bukti'] = $filename;
                    }
                }
            }

            IncomeEntry::create($data);

            return redirect()->route('projects.show', $id)
                ->with('success', 'Pendapatan berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menambah pendapatan: ' . $e->getMessage());
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

        if ($msg = $this->guardClosedDay($project, $income->tanggal)) {
            return back()->with('error', $msg);
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
            return back()->withInput()->with('error', $msg);
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
            $file = $request->file('file_bukti');
            if ($file->isValid() && $file->getSize() <= 3 * 1024 * 1024) {
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($file->getMimeType(), $allowedMime)) {
                    $filename = 'income_bukti_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
                    $file->storeAs('bukti/income', $filename, 'public');
                    $data['file_bukti'] = $filename;
                }
            }
        }

        $income->update($data);

        return redirect()->route('projects.show', $id)->with('success', 'Entri pendapatan diperbarui.');
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

        return redirect()->route('projects.show', $id)
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

        return redirect()->route('projects.show', $id)
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
            $project->delete();

            DB::commit();

            return redirect()->route('projects.index')
                ->with('success', 'Project dan seluruh riwayat biaya/pendapatan telah dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus project: ' . $e->getMessage());
        }
    }

    public function costBukti($id)
    {
        $cost = CostEntry::findOrFail($id);
        
        if (!$cost->file_bukti) {
            abort(404, 'Bukti tidak ditemukan');
        }

        $path = storage_path('app/public/bukti/cost/' . $cost->file_bukti);
        
        if (!file_exists($path)) {
            abort(404, 'Bukti tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function incomeBukti($id)
    {
        $income = IncomeEntry::findOrFail($id);
        
        if (!$income->file_bukti) {
            abort(404, 'Bukti tidak ditemukan');
        }

        $path = storage_path('app/public/bukti/income/' . $income->file_bukti);
        
        if (!file_exists($path)) {
            abort(404, 'Bukti tidak ditemukan');
        }

        return response()->file($path, [
            'Content-Type' => mime_content_type($path),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function normalizeDecimal($value, $default = null)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $clean = str_replace(['.', ' '], ['', ''], (string) $value);
        $clean = str_replace(',', '.', $clean);
        return (float) $clean;
    }

    private function guardClosedDay(Project $project, $date): ?string
    {
        if (!$project->isUmkm()) {
            return null;
        }
        if ($project->lock_closed_days === false) {
            return null;
        }
        if (app(DailyControlService::class)->isDayClosed($project, $date)) {
            $label = \Carbon\Carbon::parse($date)->format('d M Y');

            return "Tanggal {$label} sudah ditutup. Buka ulang tutup kas dulu untuk mengubah entri.";
        }

        return null;
    }
}
