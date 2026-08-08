@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <a href="{{ route('cost-centers.index') }}">Unit Bisnis</a>
    <span class="sep">/</span>
    <span class="current">{{ $project->nama_project }}</span>
@endsection

@section('content')
@php $isUmkm = $project->isUmkm(); @endphp
<div class="page-header">
    <div>
        <h2>{{ $project->nama_project }}</h2>
        <p>
            <span class="badge {{ $isUmkm ? 'badge-yellow' : 'badge-blue' }}" style="vertical-align:middle;">
                <i class="bi bi-{{ $isUmkm ? 'shop' : 'building' }}"></i> {{ $project->mode_label }}
            </span>
            · {{ $project->client ?: ($isUmkm ? 'Outlet' : 'Tanpa klien') }}
            @if($project->lokasi) · {{ $project->lokasi }} @endif
            @if($project->date_start)
                · {{ $project->date_start->format('d M Y') }}{{ $project->date_end ? ' – '.$project->date_end->format('d M Y') : '' }}
            @endif
            ·
            <span class="badge {{ $isArchived ? 'badge-gray' : 'badge-green' }}" style="vertical-align:middle;">
                <span class="status-dot {{ $isArchived ? 'archived' : 'active' }}"></span>
                {{ $isArchived ? 'Arsip' : 'Aktif' }}
            </span>
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('cost-centers.index', $isUmkm ? ['mode' => 'umkm'] : ['mode' => 'project']) }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Kembali</a>
        @if(!$isArchived)
            <button class="btn btn-outline" onclick="openModal('addCostModal')"><i class="bi bi-dash-circle"></i> {{ $isUmkm ? 'Catat Biaya Hari Ini' : 'Catat Biaya' }}</button>
            <button class="btn btn-primary" onclick="openModal('addIncomeModal')"><i class="bi bi-plus-circle"></i> {{ $isUmkm ? 'Catat Omzet' : 'Catat Pendapatan' }}</button>
        @endif
    </div>
</div>

@if($isUmkm)
@php
    $snap = $dailySnap ?? null;
    $cashCost = $snap['cost_cash'] ?? $todayCost;
    $econCost = $snap['cost_economic'] ?? $todayCost;
    $fixedDay = $snap['fixed_prorate'] ?? 0;
    $marginCash = $snap['margin_cash'] ?? $todayMargin;
    $marginEcon = $snap['margin_economic'] ?? $todayMargin;
    $isClosedToday = $snap['is_closed'] ?? false;
@endphp
{{-- UMKM: focus today --}}
<div class="kpi-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-arrow-up-circle"></i></div></div>
        <div class="kpi-label">Omzet Hari Ini</div>
        <div class="kpi-value money positive">Rp {{ number_format($todayIncome, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">{{ now()->translatedFormat('d M Y') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-cash-stack"></i></div></div>
        <div class="kpi-label">Biaya Kas</div>
        <div class="kpi-value money negative">Rp {{ number_format($cashCost, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">COGS {{ number_format($snap['cogs'] ?? 0, 0, ',', '.') }} · Ops {{ number_format($snap['ops'] ?? 0, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon yellow"><i class="bi bi-building"></i></div></div>
        <div class="kpi-label">+ Pro-rate Tetap</div>
        <div class="kpi-value" style="font-size:18px;">Rp {{ number_format($fixedDay, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">Beban ekonomi: Rp {{ number_format($econCost, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon {{ $marginEcon >= 0 ? 'blue' : 'red' }}"><i class="bi bi-graph-up-arrow"></i></div></div>
        <div class="kpi-label">Profit (Ekonomi)</div>
        <div class="kpi-value money {{ $marginEcon >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($marginEcon, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">Kas: Rp {{ number_format($marginCash, 0, ',', '.') }}</div>
    </div>
</div>

@if(!empty($snap['alerts']))
    @foreach($snap['alerts'] as $alert)
        <div class="alert alert-{{ $alert['level'] === 'danger' ? 'danger' : ($alert['level'] === 'warning' ? 'danger' : 'info') }}" style="margin-bottom:10px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>{{ $alert['title'] }}</strong>
                <div>{{ $alert['message'] }}</div>
            </div>
        </div>
    @endforeach
@endif

<div class="grid-2" style="margin-bottom:16px;align-items:start;">
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-speedometer2"></i> Kontrol Harian</h3>
            @if($isClosedToday)
                <span class="badge badge-green"><i class="bi bi-lock-fill"></i> Ditutup</span>
            @else
                <span class="badge badge-yellow"><i class="bi bi-unlock"></i> Terbuka</span>
            @endif
        </div>
        <div class="card-body">
            @if($dailyTarget)
            @php
                $pct = $snap['budget_usage_pct'] ?? $dailyUsagePct ?? 0;
                $barColor = $pct > 100 ? 'var(--danger)' : ($pct > 80 ? 'var(--warning)' : 'var(--success)');
            @endphp
            <div style="margin-bottom:14px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:12.5px;">
                    <span>Pagu biaya kas</span>
                    <span style="color:{{ $barColor }};font-weight:600;">{{ number_format($pct, 1) }}%</span>
                </div>
                <div class="progress"><div class="progress-bar" style="width:{{ min($pct, 100) }}%;background:{{ $barColor }};"></div></div>
                <div class="cell-sub" style="margin-top:6px;">Rp {{ number_format($cashCost, 0, ',', '.') }} / Rp {{ number_format($dailyTarget, 0, ',', '.') }}</div>
            </div>
            @endif

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;margin-bottom:14px;">
                <div style="background:#f8fafc;padding:10px;border-radius:10px;">
                    <div class="cell-sub">COGS / Omzet</div>
                    <strong>{{ $snap['cogs_ratio_pct'] !== null ? number_format($snap['cogs_ratio_pct'], 1).'%' : '—' }}</strong>
                    <div class="cell-sub">Batas {{ number_format(($snap['cogs_threshold'] ?? 0.45) * 100, 0) }}%</div>
                </div>
                <div style="background:#f8fafc;padding:10px;border-radius:10px;">
                    <div class="cell-sub">Bulan ini (kas)</div>
                    <strong>Rp {{ number_format($monthCost, 0, ',', '.') }}</strong>
                    <div class="cell-sub">Omzet Rp {{ number_format($monthIncome, 0, ',', '.') }}</div>
                </div>
            </div>

            @if(!$isArchived)
                @if($isClosedToday)
                    <form action="{{ route('cost-centers.dailyClose.reopen', $project->id_project) }}" method="POST" data-confirm="Buka ulang tutup kas tanggal ini?">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-input" name="tanggal" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                        </div>
                        <button class="btn btn-outline" style="width:100%;"><i class="bi bi-unlock"></i> Buka Ulang Kas</button>
                    </form>
                @else
                    <form action="{{ route('cost-centers.dailyClose.store', $project->id_project) }}" method="POST" data-confirm="Tutup kas tanggal ini? Entri akan dikunci.">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">Tanggal Tutup Kas</label>
                            <input type="date" class="form-input" name="tanggal" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catatan tutup kas</label>
                            <input type="text" class="form-input" name="notes" placeholder="Opsional">
                        </div>
                        <button class="btn btn-primary" style="width:100%;"><i class="bi bi-lock"></i> Tutup Kas</button>
                    </form>
                @endif
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-building"></i> Biaya Tetap (Pro-rate)</h3>
            @if(!$isArchived)
                <button type="button" class="btn btn-sm btn-outline" onclick="openModal('addFixedModal')"><i class="bi bi-plus"></i> Tambah</button>
            @endif
        </div>
        <div class="card-body compact">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">/bulan</th>
                            <th class="text-end">/hari</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fixedCosts as $fc)
                            @php $dayAmt = $fc->dailyAmountFor(now()); @endphp
                            <tr>
                                <td>
                                    <div class="cell-title">{{ $fc->nama }}</div>
                                    @if(!$fc->is_active)<span class="badge badge-gray">Nonaktif</span>@endif
                                </td>
                                <td class="text-end money">Rp {{ number_format($fc->amount_monthly, 0, ',', '.') }}</td>
                                <td class="text-end money">Rp {{ number_format($dayAmt, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    @if(!$isArchived)
                                    <form action="{{ route('cost-centers.fixedCosts.delete', [$project->id_project, $fc->id_fixed_cost]) }}" method="POST" data-confirm="Hapus biaya tetap ini?">
                                        @csrf
                                        <button class="btn btn-xs btn-ghost" style="color:var(--danger)"><i class="bi bi-trash3"></i></button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state" style="padding:20px;">
                                        <p>Belum ada biaya tetap</p>
                                        <div class="cell-sub">Contoh: sewa, listrik, gaji pokok → dibagi 30 hari</div>
                                        @if(!$isArchived)
                                            <button class="btn btn-sm btn-outline" style="margin-top:8px;" onclick="openModal('addFixedModal')">Tambah</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($fixedCosts->count())
                    <tfoot>
                        <tr>
                            <td><strong>Total pro-rate hari ini</strong></td>
                            <td class="text-end money"><strong>Rp {{ number_format($fixedCosts->where('is_active', true)->sum('amount_monthly'), 0, ',', '.') }}</strong></td>
                            <td class="text-end money"><strong>Rp {{ number_format($fixedDay, 0, ',', '.') }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

@if(($recentDays ?? collect())->count())
<div class="card" style="margin-bottom:18px;">
    <div class="card-header">
        <h3><i class="bi bi-calendar-week"></i> 7 Hari Terakhir</h3>
    </div>
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-end">Omzet</th>
                        <th class="text-end">Kas</th>
                        <th class="text-end">Pro-rate</th>
                        <th class="text-end">Profit</th>
                        <th class="text-end">COGS%</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentDays as $day)
                        <tr>
                            <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($day['date'])->format('d M') }}</td>
                            <td class="text-end money positive">Rp {{ number_format($day['income'], 0, ',', '.') }}</td>
                            <td class="text-end money negative">Rp {{ number_format($day['cost_cash'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($day['fixed_prorate'], 0, ',', '.') }}</td>
                            <td class="text-end money {{ $day['margin_economic'] >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($day['margin_economic'], 0, ',', '.') }}</td>
                            <td class="text-end">
                                @if($day['cogs_ratio_pct'] !== null)
                                    <span class="badge {{ $day['leak_alert'] ? 'badge-red' : 'badge-gray' }}">{{ number_format($day['cogs_ratio_pct'], 0) }}%</span>
                                @else — @endif
                            </td>
                            <td>
                                @if($day['is_closed'])
                                    <span class="badge badge-green">Closed</span>
                                @elseif($day['over_budget'] || $day['leak_alert'])
                                    <span class="badge badge-red">Alert</span>
                                @else
                                    <span class="badge badge-gray">Open</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@else
{{-- Proyek: overall KPI --}}
<div class="kpi-grid" style="grid-template-columns: repeat(3, 1fr);">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-arrow-down-circle"></i></div></div>
        <div class="kpi-label">Total Biaya</div>
        <div class="kpi-value money negative">Rp {{ number_format($project->total_cost, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">{{ $project->costEntries->count() }} entri</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-arrow-up-circle"></i></div></div>
        <div class="kpi-label">Total Pendapatan</div>
        <div class="kpi-value money positive">Rp {{ number_format($project->total_income, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">{{ $project->incomeEntries->count() }} entri</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon {{ $project->margin >= 0 ? 'blue' : 'yellow' }}"><i class="bi bi-graph-up-arrow"></i></div></div>
        <div class="kpi-label">Margin</div>
        <div class="kpi-value money {{ $project->margin >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($project->margin, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">Pendapatan − Biaya</div>
    </div>
</div>

@if($project->project_value)
@php
    $pct = $project->project_value > 0 ? ($project->total_cost / $project->project_value) * 100 : 0;
    $barColor = $pct > 100 ? 'var(--danger)' : ($pct > 80 ? 'var(--warning)' : 'var(--success)');
@endphp
<div class="card" style="margin-bottom:18px;">
    <div class="card-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12.5px;color:var(--text-secondary);">Progress budget (biaya vs nilai kontrak)</div>
                <div style="font-weight:600;margin-top:2px;">Rp {{ number_format($project->total_cost, 0, ',', '.') }}
                    <span style="color:var(--text-muted);font-weight:500;"> / Rp {{ number_format($project->project_value, 0, ',', '.') }}</span>
                </div>
            </div>
            <span class="badge" style="background:{{ $barColor }}15;color:{{ $barColor }};">{{ number_format($pct, 1) }}% terpakai</span>
        </div>
        <div class="progress">
            <div class="progress-bar" style="width:{{ min($pct, 100) }}%;background:{{ $barColor }};"></div>
        </div>
    </div>
</div>
@endif
@endif

<div class="tabs">
    <button type="button" class="tab active" data-tab="costs" onclick="showTab('costs', this)">
        Biaya <span class="count">{{ $project->costEntries->count() }}</span>
    </button>
    <button type="button" class="tab" data-tab="incomes" onclick="showTab('incomes', this)">
        {{ $isUmkm ? 'Omzet' : 'Pendapatan' }} <span class="count">{{ $project->incomeEntries->count() }}</span>
    </button>
    <button type="button" class="tab" data-tab="plans" onclick="showTab('plans', this)">
        Rencana / RAB <span class="count">{{ ($costPlans ?? collect())->count() + ($incomePlans ?? collect())->count() }}</span>
    </button>
    <button type="button" class="tab" data-tab="admins" onclick="showTab('admins', this)">
        Admin <span class="count">{{ $project->admins->count() }}</span>
    </button>
</div>

<div class="toolbar" style="margin-top:-4px;" id="entryToolbar">
    <div class="toolbar-left">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" id="entrySearch" placeholder="Cari deskripsi, tipe...">
        </div>
    </div>
    @if(!$isArchived)
    <div class="toolbar-right">
        <button class="btn btn-sm btn-outline" id="btnAddCost" onclick="openModal('addCostModal')"><i class="bi bi-plus"></i> Biaya</button>
        <button class="btn btn-sm btn-outline" id="btnAddIncome" onclick="openModal('addIncomeModal')" style="display:none;"><i class="bi bi-plus"></i> {{ $isUmkm ? 'Omzet' : 'Pendapatan' }}</button>
    </div>
    @endif
</div>

<div id="tab-costs">
    <div class="card">
        <div class="card-body compact">
            <div class="table-wrap">
                <table id="costTable">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Keterangan</th>
                            <th class="text-end">Qty</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Total</th>
                            <th>Bukti</th>
                            @if(!$isArchived)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($project->costEntries->sortByDesc('tanggal') as $cost)
                            <tr data-search="{{ strtolower(($cost->keterangan ?? '').' '.($cost->costType?->nama ?? '').' biaya') }}">
                                <td style="white-space:nowrap;">{{ $cost->tanggal->format('d M Y') }}</td>
                                <td><span class="badge badge-blue">{{ $cost->costType?->nama ?? '—' }}</span></td>
                                <td>
                                    <div class="cell-title" style="font-weight:500;">{{ $cost->keterangan ?: '—' }}</div>
                                    @if($cost->catatan)<div class="cell-sub">{{ $cost->catatan }}</div>@endif
                                </td>
                                <td class="text-end">{{ number_format($cost->qty, 2, ',', '.') }}</td>
                                <td>{{ $cost->unit ?? '—' }}</td>
                                <td class="text-end">Rp {{ number_format($cost->harga_satuan, 0, ',', '.') }}</td>
                                <td class="text-end money negative">Rp {{ number_format($cost->total, 0, ',', '.') }}</td>
                                <td>
                                    @if($cost->file_bukti)
                                        <a href="{{ route('cost-centers.costBukti', $cost->id_cost) }}" target="_blank" class="btn btn-xs btn-outline btn-icon" title="Lihat bukti"><i class="bi bi-image"></i></a>
                                    @else
                                        <span style="color:var(--text-muted)">—</span>
                                    @endif
                                </td>
                                @if(!$isArchived)
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-xs btn-outline btn-icon" title="Edit" onclick="openModal('editCost{{ $cost->id_cost }}')"><i class="bi bi-pencil"></i></button>
                                        <form action="{{ route('cost-centers.deleteCost', [$project->id_project, $cost->id_cost]) }}" method="POST" data-confirm="Hapus entri biaya ini?">
                                            @csrf
                                            <button class="btn btn-xs btn-ghost btn-icon" style="color:var(--danger)" title="Hapus"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ !$isArchived ? 9 : 8 }}">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada entri biaya</p>
                                        @if(!$isArchived)
                                            <button class="btn btn-sm btn-primary" onclick="openModal('addCostModal')"><i class="bi bi-plus"></i> Catat biaya</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="tab-incomes" style="display:none;">
    <div class="card">
        <div class="card-body compact">
            <div class="table-wrap">
                <table id="incomeTable">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Keterangan</th>
                            <th class="text-end">Qty</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Total</th>
                            <th>Bukti</th>
                            @if(!$isArchived)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($project->incomeEntries->sortByDesc('tanggal') as $income)
                            <tr data-search="{{ strtolower(($income->keterangan ?? '').' '.($income->incomeType?->nama ?? '').' pendapatan') }}">
                                <td style="white-space:nowrap;">{{ $income->tanggal->format('d M Y') }}</td>
                                <td><span class="badge badge-green">{{ $income->incomeType?->nama ?? '—' }}</span></td>
                                <td>
                                    <div class="cell-title" style="font-weight:500;">{{ $income->keterangan ?: '—' }}</div>
                                    @if($income->catatan)<div class="cell-sub">{{ $income->catatan }}</div>@endif
                                </td>
                                <td class="text-end">{{ number_format($income->qty, 2, ',', '.') }}</td>
                                <td>{{ $income->unit ?? '—' }}</td>
                                <td class="text-end">Rp {{ number_format($income->harga_satuan, 0, ',', '.') }}</td>
                                <td class="text-end money positive">Rp {{ number_format($income->total, 0, ',', '.') }}</td>
                                <td>
                                    @if($income->file_bukti)
                                        <a href="{{ route('cost-centers.incomeBukti', $income->id_income) }}" target="_blank" class="btn btn-xs btn-outline btn-icon" title="Lihat bukti"><i class="bi bi-image"></i></a>
                                    @else
                                        <span style="color:var(--text-muted)">—</span>
                                    @endif
                                </td>
                                @if(!$isArchived)
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-xs btn-outline btn-icon" title="Edit" onclick="openModal('editIncome{{ $income->id_income }}')"><i class="bi bi-pencil"></i></button>
                                        <form action="{{ route('cost-centers.deleteIncome', [$project->id_project, $income->id_income]) }}" method="POST" data-confirm="Hapus entri pendapatan ini?">
                                            @csrf
                                            <button class="btn btn-xs btn-ghost btn-icon" style="color:var(--danger)" title="Hapus"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ !$isArchived ? 9 : 8 }}">
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada entri pendapatan</p>
                                        @if(!$isArchived)
                                            <button class="btn btn-sm btn-success" onclick="openModal('addIncomeModal')"><i class="bi bi-plus"></i> Catat pendapatan</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Plans / RAB --}}
<div id="tab-plans" style="display:none;">
    <div class="grid-2" style="align-items:start;">
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-clipboard-data"></i> Rencana Biaya</h3>
                @if(!$isArchived)
                    <button type="button" class="btn btn-sm btn-outline" onclick="openModal('addCostPlanModal')"><i class="bi bi-plus"></i> Tambah</button>
                @endif
            </div>
            <div class="card-body compact">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th class="text-end">Rencana</th>
                                <th class="text-end">Realisasi</th>
                                <th class="text-end">%</th>
                                @if(!$isArchived)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($costPlans as $plan)
                                @php
                                    $actual = (float) ($actualCostByType[$plan->id_cost_type] ?? 0);
                                    $pct = $plan->amount > 0 ? ($actual / $plan->amount) * 100 : null;
                                @endphp
                                <tr>
                                    <td><div class="cell-title">{{ $plan->costType?->nama ?? '—' }}</div></td>
                                    <td class="text-end money">Rp {{ number_format($plan->amount, 0, ',', '.') }}</td>
                                    <td class="text-end money negative">Rp {{ number_format($actual, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        @if($pct !== null)
                                            <span class="badge {{ $pct > 100 ? 'badge-red' : ($pct > 80 ? 'badge-yellow' : 'badge-green') }}">{{ number_format($pct, 0) }}%</span>
                                        @else — @endif
                                    </td>
                                    @if(!$isArchived)
                                    <td class="text-end">
                                        <form action="{{ route('cost-centers.costPlans.delete', [$project->id_project, $plan->id]) }}" method="POST" data-confirm="Hapus rencana?">
                                            @csrf
                                            <button class="btn btn-xs btn-ghost" style="color:var(--danger)"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="empty-state" style="padding:24px;"><p>Belum ada rencana biaya</p></div></td></tr>
                            @endforelse
                        </tbody>
                        @if($costPlans->count())
                        <tfoot>
                            <tr>
                                <td><strong>Total</strong></td>
                                <td class="text-end money">Rp {{ number_format($planCostTotal, 0, ',', '.') }}</td>
                                <td class="text-end money">Rp {{ number_format($project->total_cost, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    @if($planCostTotal > 0)
                                        {{ number_format(($project->total_cost / $planCostTotal) * 100, 0) }}%
                                    @endif
                                </td>
                                @if(!$isArchived)<td></td>@endif
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-graph-up"></i> Rencana Pendapatan</h3>
                @if(!$isArchived)
                    <button type="button" class="btn btn-sm btn-outline" onclick="openModal('addIncomePlanModal')"><i class="bi bi-plus"></i> Tambah</button>
                @endif
            </div>
            <div class="card-body compact">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th class="text-end">Rencana</th>
                                <th class="text-end">Realisasi</th>
                                <th class="text-end">%</th>
                                @if(!$isArchived)<th></th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomePlans as $plan)
                                @php
                                    $actual = (float) ($actualIncomeByType[$plan->id_income_type] ?? 0);
                                    $pct = $plan->amount > 0 ? ($actual / $plan->amount) * 100 : null;
                                @endphp
                                <tr>
                                    <td><div class="cell-title">{{ $plan->incomeType?->nama ?? '—' }}</div></td>
                                    <td class="text-end money">Rp {{ number_format($plan->amount, 0, ',', '.') }}</td>
                                    <td class="text-end money positive">Rp {{ number_format($actual, 0, ',', '.') }}</td>
                                    <td class="text-end">
                                        @if($pct !== null)
                                            <span class="badge {{ $pct >= 100 ? 'badge-green' : 'badge-yellow' }}">{{ number_format($pct, 0) }}%</span>
                                        @else — @endif
                                    </td>
                                    @if(!$isArchived)
                                    <td class="text-end">
                                        <form action="{{ route('cost-centers.incomePlans.delete', [$project->id_project, $plan->id]) }}" method="POST" data-confirm="Hapus rencana?">
                                            @csrf
                                            <button class="btn btn-xs btn-ghost" style="color:var(--danger)"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="empty-state" style="padding:24px;"><p>Belum ada rencana pendapatan</p></div></td></tr>
                            @endforelse
                        </tbody>
                        @if($incomePlans->count())
                        <tfoot>
                            <tr>
                                <td><strong>Total</strong></td>
                                <td class="text-end money">Rp {{ number_format($planIncomeTotal, 0, ',', '.') }}</td>
                                <td class="text-end money">Rp {{ number_format($project->total_income, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    @if($planIncomeTotal > 0)
                                        {{ number_format(($project->total_income / $planIncomeTotal) * 100, 0) }}%
                                    @endif
                                </td>
                                @if(!$isArchived)<td></td>@endif
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Admins --}}
<div id="tab-admins" style="display:none;">
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-people"></i> Admin Unit</h3>
        </div>
        <div class="card-body">
            @if(!$isArchived)
            <form action="{{ route('cost-centers.admins.sync', $project->id_project) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Pilih admin yang boleh kelola unit ini</label>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;">
                        @foreach($availableAdmins as $admin)
                            <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;cursor:pointer;">
                                <input type="checkbox" name="admin_ids[]" value="{{ $admin->id_pengguna }}" @checked(in_array($admin->id_pengguna, $assignedAdminIds ?? []))>
                                <span>
                                    <strong style="font-size:13px;">{{ $admin->nama_lengkap }}</strong>
                                    <div class="cell-sub">{{ $admin->akun?->role ?? '—' }} · {{ $admin->jabatan ?? '—' }}</div>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Admin</button>
            </form>
            @else
                <ul style="padding-left:18px;">
                    @forelse($project->admins as $a)
                        <li>{{ $a->nama_lengkap }}</li>
                    @empty
                        <li class="cell-sub">Belum ada admin</li>
                    @endforelse
                </ul>
            @endif
        </div>
    </div>
</div>

@if(!$isArchived)
{{-- Add Cost --}}
<div class="modal-backdrop" id="addCostModal">
    <div class="modal modal-lg">
        <form action="{{ route('cost-centers.addCost', $project->id_project) }}" method="POST" enctype="multipart/form-data" id="costForm">
            @csrf
            <div class="modal-header">
                <h3>Catat Biaya</h3>
                <button type="button" class="modal-close" onclick="closeModal('addCostModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipe Biaya <span class="req">*</span></label>
                        <select class="form-select" name="id_cost_type" required>
                            <option value="" disabled selected>Cari atau pilih tipe biaya...</option>
                            @foreach($costTypesByKategori as $kat => $types)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $kat)) }}">
                                    @foreach($types as $type)
                                        <option value="{{ $type->id_cost_type }}" data-unit="{{ $type->default_unit }}">{{ $type->nama }}@if($type->kode) ({{ $type->kode }})@endif</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="req">*</span></label>
                        <input type="date" class="form-input" name="tanggal" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-input" name="keterangan" placeholder="Contoh: Beli semen 50 sak">
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Qty <span class="req">*</span></label>
                        <input type="number" class="form-input calc-qty" name="qty" step="0.01" min="0.01" value="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="unit">
                            <option value="">Pilih</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->nama }}">{{ $unit->nama }}@if($unit->simbol) ({{ $unit->simbol }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Satuan</label>
                        <div class="input-prefix">
                            <span>Rp</span>
                            <input type="text" class="form-input calc-price" name="harga_satuan" data-money placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Total</label>
                    <div class="input-prefix">
                        <span>Rp</span>
                        <input type="text" class="form-input calc-total" name="total" data-money placeholder="0">
                    </div>
                    <div class="form-hint">Otomatis dihitung dari Qty × Harga. Bisa diubah manual.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-textarea" name="catatan" rows="2" placeholder="Opsional"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Bukti (foto)</label>
                    <input type="file" class="form-input" name="file_bukti" accept="image/*">
                    <div class="form-hint">JPG, PNG, WEBP · maks 3MB</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCostModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Biaya</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Income --}}
<div class="modal-backdrop" id="addIncomeModal">
    <div class="modal modal-lg">
        <form action="{{ route('cost-centers.addIncome', $project->id_project) }}" method="POST" enctype="multipart/form-data" id="incomeForm">
            @csrf
            <div class="modal-header">
                <h3>Catat Pendapatan</h3>
                <button type="button" class="modal-close" onclick="closeModal('addIncomeModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipe Pendapatan <span class="req">*</span></label>
                        <select class="form-select" name="id_income_type" required>
                            <option value="" disabled selected>Cari atau pilih tipe pendapatan...</option>
                            @foreach($incomeTypesByKategori as $kat => $types)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $kat)) }}">
                                    @foreach($types as $type)
                                        <option value="{{ $type->id_income_type }}">{{ $type->nama }}@if($type->kode) ({{ $type->kode }})@endif</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="req">*</span></label>
                        <input type="date" class="form-input" name="tanggal" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-input" name="keterangan" placeholder="Contoh: Termyn 1">
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Qty <span class="req">*</span></label>
                        <input type="number" class="form-input calc-qty" name="qty" step="0.01" min="0.01" value="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="unit">
                            <option value="">Pilih</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->nama }}">{{ $unit->nama }}@if($unit->simbol) ({{ $unit->simbol }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Satuan</label>
                        <div class="input-prefix">
                            <span>Rp</span>
                            <input type="text" class="form-input calc-price" name="harga_satuan" data-money placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Total</label>
                    <div class="input-prefix">
                        <span>Rp</span>
                        <input type="text" class="form-input calc-total" name="total" data-money placeholder="0">
                    </div>
                    <div class="form-hint">Otomatis dihitung dari Qty × Harga. Bisa diubah manual.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-textarea" name="catatan" rows="2" placeholder="Opsional"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Bukti (foto)</label>
                    <input type="file" class="form-input" name="file_bukti" accept="image/*">
                    <div class="form-hint">JPG, PNG, WEBP · maks 3MB</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addIncomeModal')">Batal</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Simpan Pendapatan</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Edit cost modals --}}
@foreach($project->costEntries as $cost)
<div class="modal-backdrop" id="editCost{{ $cost->id_cost }}">
    <div class="modal modal-lg">
        <form action="{{ route('cost-centers.updateCost', [$project->id_project, $cost->id_cost]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h3>Edit Biaya</h3>
                <button type="button" class="modal-close" onclick="closeModal('editCost{{ $cost->id_cost }}')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipe Biaya <span class="req">*</span></label>
                        <select class="form-select" name="id_cost_type" required>
                            @foreach($costTypesByKategori as $kat => $types)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $kat)) }}">
                                    @foreach($types as $type)
                                        <option value="{{ $type->id_cost_type }}" @selected($cost->id_cost_type == $type->id_cost_type)>{{ $type->nama }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="req">*</span></label>
                        <input type="date" class="form-input" name="tanggal" value="{{ $cost->tanggal?->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-input" name="keterangan" value="{{ $cost->keterangan }}">
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Qty <span class="req">*</span></label>
                        <input type="number" class="form-input" name="qty" step="0.01" min="0.01" value="{{ $cost->qty }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="unit">
                            <option value="">Pilih</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->nama }}" @selected($cost->unit === $unit->nama)>{{ $unit->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Satuan</label>
                        <div class="input-prefix"><span>Rp</span>
                            <input type="text" class="form-input" name="harga_satuan" data-money value="{{ $cost->harga_satuan ? number_format($cost->harga_satuan, 0, ',', '.') : '' }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Total</label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input" name="total" data-money value="{{ $cost->total ? number_format($cost->total, 0, ',', '.') : '' }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-textarea" name="catatan" rows="2">{{ $cost->catatan }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ganti bukti</label>
                    <input type="file" class="form-input" name="file_bukti" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editCost{{ $cost->id_cost }}')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endforeach

@foreach($project->incomeEntries as $income)
<div class="modal-backdrop" id="editIncome{{ $income->id_income }}">
    <div class="modal modal-lg">
        <form action="{{ route('cost-centers.updateIncome', [$project->id_project, $income->id_income]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h3>Edit Pendapatan</h3>
                <button type="button" class="modal-close" onclick="closeModal('editIncome{{ $income->id_income }}')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipe <span class="req">*</span></label>
                        <select class="form-select" name="id_income_type" required>
                            @foreach($incomeTypesByKategori as $kat => $types)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $kat)) }}">
                                    @foreach($types as $type)
                                        <option value="{{ $type->id_income_type }}" @selected($income->id_income_type == $type->id_income_type)>{{ $type->nama }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal <span class="req">*</span></label>
                        <input type="date" class="form-input" name="tanggal" value="{{ $income->tanggal?->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-input" name="keterangan" value="{{ $income->keterangan }}">
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Qty <span class="req">*</span></label>
                        <input type="number" class="form-input" name="qty" step="0.01" min="0.01" value="{{ $income->qty }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="unit">
                            <option value="">Pilih</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->nama }}" @selected($income->unit === $unit->nama)>{{ $unit->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Harga Satuan</label>
                        <div class="input-prefix"><span>Rp</span>
                            <input type="text" class="form-input" name="harga_satuan" data-money value="{{ $income->harga_satuan ? number_format($income->harga_satuan, 0, ',', '.') : '' }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Total</label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input" name="total" data-money value="{{ $income->total ? number_format($income->total, 0, ',', '.') : '' }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea class="form-textarea" name="catatan" rows="2">{{ $income->catatan }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Ganti bukti</label>
                    <input type="file" class="form-input" name="file_bukti" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editIncome{{ $income->id_income }}')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div class="modal-backdrop" id="addCostPlanModal">
    <div class="modal">
        <form action="{{ route('cost-centers.costPlans.store', $project->id_project) }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Rencana Biaya</h3>
                <button type="button" class="modal-close" onclick="closeModal('addCostPlanModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Tipe Biaya <span class="req">*</span></label>
                    <select class="form-select" name="id_cost_type" required>
                        @foreach($costTypesByKategori as $kat => $types)
                            <optgroup label="{{ ucfirst(str_replace('_', ' ', $kat)) }}">
                                @foreach($types as $type)
                                    <option value="{{ $type->id_cost_type }}">{{ $type->nama }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Rencana <span class="req">*</span></label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input" name="amount" data-money required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCostPlanModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-backdrop" id="addIncomePlanModal">
    <div class="modal">
        <form action="{{ route('cost-centers.incomePlans.store', $project->id_project) }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Rencana Pendapatan</h3>
                <button type="button" class="modal-close" onclick="closeModal('addIncomePlanModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Tipe Pendapatan <span class="req">*</span></label>
                    <select class="form-select" name="id_income_type" required>
                        @foreach($incomeTypesByKategori as $kat => $types)
                            <optgroup label="{{ ucfirst(str_replace('_', ' ', $kat)) }}">
                                @foreach($types as $type)
                                    <option value="{{ $type->id_income_type }}">{{ $type->nama }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Rencana <span class="req">*</span></label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input" name="amount" data-money required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addIncomePlanModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

@if($isUmkm && !$isArchived)
<div class="modal-backdrop" id="addFixedModal">
    <div class="modal">
        <form action="{{ route('cost-centers.fixedCosts.store', $project->id_project) }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Biaya Tetap</h3>
                <button type="button" class="modal-close" onclick="closeModal('addFixedModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" style="margin-bottom:14px;">
                    <i class="bi bi-info-circle"></i>
                    <span>Nominal bulanan dibagi jumlah hari bulan berjalan sebagai beban harian (pro-rate).</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama" required placeholder="Sewa tempat">
                </div>
                <div class="form-group">
                    <label class="form-label">Nominal / bulan <span class="req">*</span></label>
                    <div class="input-prefix">
                        <span>Rp</span>
                        <input type="text" class="form-input" name="amount_monthly" data-money required placeholder="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe biaya (opsional)</label>
                    <select class="form-select" name="id_cost_type">
                        <option value="">—</option>
                        @foreach($costTypesByKategori as $kat => $types)
                            <optgroup label="{{ ucfirst(str_replace('_', ' ', $kat)) }}">
                                @foreach($types as $type)
                                    <option value="{{ $type->id_cost_type }}">{{ $type->nama }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mulai berlaku</label>
                        <input type="date" class="form-input" name="start_date" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berakhir</label>
                        <input type="date" class="form-input" name="end_date">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <input type="text" class="form-input" name="catatan" placeholder="Opsional">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addFixedModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function showTab(tab, el) {
    ['costs','incomes','plans','admins'].forEach(t => {
        const node = document.getElementById('tab-' + t);
        if (node) node.style.display = t === tab ? 'block' : 'none';
    });
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    const toolbar = document.getElementById('entryToolbar');
    if (toolbar) toolbar.style.display = (tab === 'costs' || tab === 'incomes') ? '' : 'none';
    const btnCost = document.getElementById('btnAddCost');
    const btnIncome = document.getElementById('btnAddIncome');
    if (btnCost && btnIncome) {
        btnCost.style.display = tab === 'costs' ? '' : 'none';
        btnIncome.style.display = tab === 'incomes' ? '' : 'none';
    }
    if (tab === 'costs' || tab === 'incomes') filterEntries();
}

function parseMoney(v) {
    return Number(String(v || '').replace(/\./g, '').replace(/,/g, '.')) || 0;
}
function bindCalc(form) {
    if (!form) return;
    const qty = form.querySelector('.calc-qty');
    const price = form.querySelector('.calc-price');
    const total = form.querySelector('.calc-total');
    let manualTotal = false;
    total?.addEventListener('input', () => { manualTotal = true; });
    function recalc() {
        if (manualTotal) return;
        const t = (Number(qty?.value) || 0) * parseMoney(price?.value);
        if (total) {
            total.value = t ? Math.round(t).toLocaleString('id-ID') : '';
        }
    }
    qty?.addEventListener('input', () => { manualTotal = false; recalc(); });
    price?.addEventListener('input', () => { manualTotal = false; recalc(); });
}
bindCalc(document.getElementById('costForm'));
bindCalc(document.getElementById('incomeForm'));

function filterEntries() {
    const q = (document.getElementById('entrySearch')?.value || '').toLowerCase().trim();
    const activeTab = document.querySelector('.tab.active')?.dataset.tab || 'costs';
    const table = document.getElementById(activeTab === 'costs' ? 'costTable' : 'incomeTable');
    table?.querySelectorAll('tbody tr[data-search]').forEach(row => {
        row.style.display = !q || row.dataset.search.includes(q) ? '' : 'none';
    });
}
document.getElementById('entrySearch')?.addEventListener('input', filterEntries);
</script>
@endpush
