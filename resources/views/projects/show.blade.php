@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <a href="{{ route('cost-centers.index') }}">Keberangkatan</a>
    <span class="sep">/</span>
    <span class="current">{{ $project->nama_project }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $project->nama_project }}</h2>
        <p>
            <span class="badge badge-blue" style="vertical-align:middle;">
                <i class="bi bi-airplane"></i> {{ $project->mode_label }}
            </span>
            · {{ $project->client ?: 'Tanpa penyelenggara' }}
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
        <a href="{{ route('cost-centers.index') }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Kembali</a>
        @if(!$isArchived)
            <button class="btn btn-outline" onclick="openModal('addCostModal')"><i class="bi bi-dash-circle"></i> Catat Biaya</button>
            <button class="btn btn-primary" onclick="openModal('addIncomeModal')"><i class="bi bi-plus-circle"></i> Catat Pendapatan</button>
        @endif
    </div>
</div>

{{-- Keberangkatan: overall KPI --}}
<div class="kpi-grid">
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

@php
    $cp = $cashPosition ?? null;
    $fc = $cashForecast ?? null;
    $cs = collect($cashSeries ?? []);
    $maxFlow = max(1, $cs->max('in'), $cs->max('out'));
@endphp
@if($cp)
<div class="card" style="margin-bottom:18px;">
    <div class="card-header">
        <h3><i class="bi bi-cash-stack"></i> Kas Berjalan</h3>
        <span class="cell-sub">Saldo awal + pendapatan − biaya s/d {{ \Carbon\Carbon::parse($cp['date'])->format('d M Y') }}</span>
    </div>
    <div class="card-body">
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-wallet2"></i></div></div>
                <div class="kpi-label">Saldo Kas Hari Ini</div>
                <div class="kpi-value money {{ $cp['balance'] >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($cp['balance'], 0, ',', '.') }}</div>
                <div class="cell-sub">Awal: Rp {{ number_format($cp['opening'], 0, ',', '.') }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-arrow-up-circle"></i></div></div>
                <div class="kpi-label">Pemasukan kumulatif</div>
                <div class="kpi-value money positive">Rp {{ number_format($cp['income_to_date'], 0, ',', '.') }}</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-arrow-down-circle"></i></div></div>
                <div class="kpi-label">Pengeluaran kumulatif</div>
                <div class="kpi-value money negative">Rp {{ number_format($cp['cost_to_date'], 0, ',', '.') }}</div>
            </div>
            @if($fc)
            <div class="kpi-card">
                <div class="kpi-top"><div class="kpi-icon {{ $fc['over_projected'] ? 'yellow' : 'blue' }}"><i class="bi bi-hourglass-split"></i></div></div>
                <div class="kpi-label">Proyeksi akhir</div>
                <div class="kpi-value money {{ $fc['over_projected'] ? 'negative' : 'positive' }}">
                    {{ $fc['projected_end_cost'] !== null ? 'Rp '.number_format($fc['projected_end_cost'], 0, ',', '.') : '—' }}
                </div>
                <div class="cell-sub">
                    @if($fc['days_to_deplete'] !== null)
                        Budget habis ±{{ $fc['days_to_deplete'] }} hari
                    @else
                        Burn rata {{ $fc['window_days'] }} hari: Rp {{ number_format($fc['net_burn_daily'], 0, ',', '.') }}/hari
                    @endif
                </div>
            </div>
            @endif
        </div>

        @if($cp['is_negative'])
            <div class="alert alert-danger" style="margin-top:12px;">
                <i class="bi bi-exclamation-triangle"></i> Saldo kas negatif. Cek pencatatan pendapatan atau set saldo awal keberangkatan.
            </div>
        @endif

        @if($cs->count() > 1)
        <div style="margin-top:14px;">
            <div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--text-secondary);margin-bottom:6px;">
                <span>Arus kas 30 hari terakhir</span>
                <span><span style="color:var(--success);">■</span> Masuk &nbsp; <span style="color:var(--danger);">■</span> Keluar</span>
            </div>
            <div style="display:flex;align-items:flex-end;gap:2px;height:70px;">
                @foreach($cs as $day)
                    <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-end;gap:1px;height:100%;" title="{{ $day['label'] }}: masuk Rp {{ number_format($day['in'], 0, ',', '.') }}, keluar Rp {{ number_format($day['out'], 0, ',', '.') }}, saldo Rp {{ number_format($day['balance'], 0, ',', '.') }}">
                        <div style="background:var(--success);height:{{ round($day['in'] / $maxFlow * 50) }}%;min-height:{{ $day['in'] > 0 ? 2 : 0 }}px;border-radius:1px;"></div>
                        <div style="background:var(--danger);height:{{ round($day['out'] / $maxFlow * 50) }}%;min-height:{{ $day['out'] > 0 ? 2 : 0 }}px;border-radius:1px;"></div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endif

<div class="tabs">
    <button type="button" class="tab active" data-tab="costs" onclick="showTab('costs', this)">
        Biaya <span class="count">{{ $project->costEntries->count() }}</span>
    </button>
    <button type="button" class="tab" data-tab="incomes" onclick="showTab('incomes', this)">
        Pendapatan <span class="count">{{ $project->incomeEntries->count() }}</span>
    </button>
    <button type="button" class="tab" data-tab="plans" onclick="showTab('plans', this)">
        Rencana / RAB <span class="count">{{ ($costPlans ?? collect())->count() + ($incomePlans ?? collect())->count() }}</span>
    </button>
    <button type="button" class="tab" data-tab="admins" onclick="showTab('admins', this)">
        Admin <span class="count">{{ $project->admins->count() }}</span>
    </button>
    <button type="button" class="tab" data-tab="investor" onclick="showTab('investor', this)">
        Investor <span class="count">{{ $investor ? 1 : 0 }}</span>
    </button>
    @php $galleryRoute = request()->segment(1) === 'projects' ? 'projects' : 'cost-centers'; @endphp
    <a href="{{ route("{$galleryRoute}.gallery", $project->id_project) }}"
       class="tab" style="text-decoration:none;">
        <i class="bi bi-images"></i> Galeri
        @if($project->galleries_count ?? false)
            <span class="count">{{ $project->galleries_count }}</span>
        @endif
    </a>
    <button type="button" class="tab" data-tab="delete" onclick="showTab('delete', this)" style="color:var(--danger);">
        Hapus <i class="bi bi-trash"></i>
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
        <button class="btn btn-sm btn-outline" id="btnAddIncome" onclick="openModal('addIncomeModal')" style="display:none;"><i class="bi bi-plus"></i> Pendapatan</button>
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
                                    @if($cost->mata_uang !== 'IDR' && $cost->amount_valas)
                                        <div class="cell-sub" style="color:var(--text-secondary);">
                                            {{ number_format($cost->amount_valas, 2, ',', '.') }} {{ $cost->mata_uang }} @ {{ number_format($cost->kurs, 2, ',', '.') }}
                                            ({{ $cost->kurs_sumber === 'bi' ? 'Kurs BI' : 'Manual' }})
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($cost->qty, 2, ',', '.') }}</td>
                                <td>{{ $cost->unit ?? '—' }}</td>
                                <td class="text-end">Rp {{ number_format($cost->harga_satuan, 0, ',', '.') }}</td>
                                <td class="text-end money negative">Rp {{ number_format($cost->total, 0, ',', '.') }}</td>
                                <td>
                                    <div class="bukti-wrap">
                                        @forelse($cost->gallery as $g)
                                            <div class="bukti-item">
                                                <a href="{{ route("{$galleryRoute}.gallery.serve", [$project->id_project, $g->id_gallery]) }}" target="_blank" title="{{ $g->original_name }}">
                                                    @if($g->file_type === 'image')
                                                        <img src="{{ route("{$galleryRoute}.gallery.serve", [$project->id_project, $g->id_gallery]) }}" alt="{{ $g->original_name }}">
                                                    @elseif($g->file_type === 'video')
                                                        <span class="bukti-icon video"><i class="bi bi-film"></i></span>
                                                    @else
                                                        <span class="bukti-icon doc"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                                                    @endif
                                                </a>
                                                @if(!$isArchived)
                                                    <form action="{{ route("{$galleryRoute}.gallery.destroy", [$project->id_project, $g->id_gallery]) }}" method="POST" data-confirm="Hapus file {{ $g->original_name }}?" class="bukti-del">
                                                        @csrf
                                                        <button type="submit" title="Hapus"><i class="bi bi-x"></i></button>
                                                    </form>
                                                @endif
                                            </div>
                                        @empty
                                            @if($cost->file_bukti)
                                                <a href="{{ route('cost-centers.costBukti', $cost->id_cost) }}" target="_blank" class="btn btn-xs btn-outline btn-icon" title="Lihat bukti"><i class="bi bi-image"></i></a>
                                            @else
                                                <span style="color:var(--text-muted)">—</span>
                                            @endif
                                        @endforelse
                                        @if(!$isArchived)
                                            <button type="button" class="btn btn-xs btn-outline btn-icon" title="Unggah bukti" onclick="openEntryGallery('{{ $project->id_project }}', 'cost', '{{ $cost->id_cost }}')"><i class="bi bi-plus-lg"></i></button>
                                        @endif
                                    </div>
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
                                    <div class="bukti-wrap">
                                        @forelse($income->gallery as $g)
                                            <div class="bukti-item">
                                                <a href="{{ route("{$galleryRoute}.gallery.serve", [$project->id_project, $g->id_gallery]) }}" target="_blank" title="{{ $g->original_name }}">
                                                    @if($g->file_type === 'image')
                                                        <img src="{{ route("{$galleryRoute}.gallery.serve", [$project->id_project, $g->id_gallery]) }}" alt="{{ $g->original_name }}">
                                                    @elseif($g->file_type === 'video')
                                                        <span class="bukti-icon video"><i class="bi bi-film"></i></span>
                                                    @else
                                                        <span class="bukti-icon doc"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                                                    @endif
                                                </a>
                                                @if(!$isArchived)
                                                    <form action="{{ route("{$galleryRoute}.gallery.destroy", [$project->id_project, $g->id_gallery]) }}" method="POST" data-confirm="Hapus file {{ $g->original_name }}?" class="bukti-del">
                                                        @csrf
                                                        <button type="submit" title="Hapus"><i class="bi bi-x"></i></button>
                                                    </form>
                                                @endif
                                            </div>
                                        @empty
                                            @if($income->file_bukti)
                                                <a href="{{ route('cost-centers.incomeBukti', $income->id_income) }}" target="_blank" class="btn btn-xs btn-outline btn-icon" title="Lihat bukti"><i class="bi bi-image"></i></a>
                                            @else
                                                <span style="color:var(--text-muted)">—</span>
                                            @endif
                                        @endforelse
                                        @if(!$isArchived)
                                            <button type="button" class="btn btn-xs btn-outline btn-icon" title="Unggah bukti" onclick="openEntryGallery('{{ $project->id_project }}', 'income', '{{ $income->id_income }}')"><i class="bi bi-plus-lg"></i></button>
                                        @endif
                                    </div>
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
    @if(!empty($groupSummaries))
    <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
            <h3><i class="bi bi-diagram-3"></i> Progress per Kelompok</h3>
            <a href="{{ route('cost-groups.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-sliders"></i> Kelola Kelompok</a>
        </div>
        <div class="card-body">
            <div class="grid-3">
                @foreach($groupSummaries as $g)
                    @php
                        $barColor = $g['pct'] !== null ? ($g['pct'] > 100 ? 'var(--danger)' : ($g['pct'] > 80 ? 'var(--warning)' : 'var(--success)')) : 'var(--border-strong)';
                    @endphp
                    <div class="kpi-card" style="padding:14px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <strong style="font-size:13px;">{{ $g['nama'] }}</strong>
                            <span class="badge {{ $g['pct'] !== null ? ($g['pct'] > 100 ? 'badge-red' : ($g['pct'] > 80 ? 'badge-yellow' : 'badge-green')) : 'badge-gray' }}">
                                {{ $g['pct'] !== null ? number_format($g['pct']).'%' : '—' }}
                            </span>
                        </div>
                        <div class="cell-sub" style="margin-bottom:8px;">Realisasi Rp {{ number_format($g['actual'], 0, ',', '.') }}</div>
                        <div class="progress">
                            <div class="progress-bar" style="width:{{ $g['pct'] !== null ? min($g['pct'], 100) : 0 }}%;background:{{ $barColor }};"></div>
                        </div>
                        <div class="cell-sub" style="margin-top:6px;">Rencana Rp {{ number_format($g['plan'], 0, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
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
            <h3><i class="bi bi-people"></i> Admin Keberangkatan</h3>
        </div>
        <div class="card-body">
            @if(!$isArchived)
            <form action="{{ route('cost-centers.admins.sync', $project->id_project) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Pilih admin yang boleh kelola keberangkatan ini</label>
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

{{-- Investor --}}
<div id="tab-investor" style="display:none;">
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-person-badge"></i> Akun Investor</h3>
        </div>
        <div class="card-body">

            @if(session('investor_created'))
            @php $ic = session('investor_created'); @endphp
            <div class="alert alert-success" style="margin-bottom:16px;padding:14px 16px;border-radius:10px;background:var(--success-light,#ecfdf5);border:1px solid var(--success,#10b981);color:var(--success-dark,#065f46);">
                <strong><i class="bi bi-check-circle"></i> Akun investor berhasil dibuat / direset.</strong><br>
                <span>Username: <code>{{ $ic['username'] }}</code></span><br>
                <span>Password: <code>{{ $ic['password'] }}</code></span><br>
                <small>Simpan password ini sekarang. Tidak bisa ditampilkan lagi.</small>
            </div>
            @endif

            @if($errors->has('investor'))
            <div class="alert alert-danger" style="margin-bottom:16px;padding:14px 16px;border-radius:10px;background:#fef2f2;border:1px solid #ef4444;color:#991b1b;">
                <i class="bi bi-exclamation-triangle"></i> {{ $errors->first('investor') }}
            </div>
            @endif

            @if($investor)
            {{-- Investor sudah ada --}}
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 16px;border:1px solid var(--border);border-radius:10px;margin-bottom:16px;">
                <div style="width:40px;height:40px;border-radius:50%;background:var(--primary-light,#eff6ff);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-person-badge" style="font-size:18px;color:var(--primary);"></i>
                </div>
                <div>
                    <div style="font-weight:600;">{{ $investor->akun?->pengguna?->nama_lengkap ?? '—' }}
                        @if($investor->akun->is_active === '1')
                        <span class="badge badge-green">Aktif</span>
                        @else
                        <span class="badge badge-gray">Nonaktif</span>
                        @endif
                    </div>
                    <div class="cell-sub">Username: <code>{{ $investor->akun?->username }}</code></div>
                </div>
                <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;">
                    <form action="{{ route('cost-centers.investor.resetPassword', $project->id_project) }}" method="POST" data-confirm="Reset password investor?">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline"><i class="bi bi-key"></i> Reset Password</button>
                    </form>
                    <form action="{{ route('cost-centers.investor.toggle', $project->id_project) }}" method="POST" data-confirm="{{ $investor->akun->is_active === '1' ? 'Nonaktifkan akun investor ini? Investor tidak bisa login.' : 'Aktifkan kembali akun investor ini?' }}">
                        @csrf
                        @if($investor->akun->is_active === '1')
                        <button type="submit" class="btn btn-sm btn-outline"><i class="bi bi-pause-circle"></i> Nonaktifkan</button>
                        @else
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-play-circle"></i> Aktifkan</button>
                        @endif
                    </form>
                    <form action="{{ route('cost-centers.investor.delete', $project->id_project) }}" method="POST" data-confirm="Hapus akun investor ini? Akun tidak dapat dikembalikan.">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Hapus</button>
                    </form>
                </div>
            </div>
            @else
            {{-- Belum ada investor --}}
            @if(!$isArchived)
            <p style="color:var(--text-secondary);margin-bottom:16px;">Keberangkatan ini belum memiliki akun investor. Buat akun untuk berbagi akses read-only kepada investor.</p>
            <form action="{{ route('cost-centers.investor.store', $project->id_project) }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Investor <span class="req">*</span></label>
                        <input type="text" class="form-input" name="nama_lengkap" required placeholder="cth. Budi Santoso">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username Login <span class="req">*</span></label>
                        <input type="text" class="form-input" name="username" required placeholder="cth. investor.budi" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <small class="cell-sub"><i class="bi bi-info-circle"></i> Password akan digenerate otomatis dan ditampilkan sekali setelah akun dibuat.</small>
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus"></i> Buat Akun Investor</button>
            </form>
            @else
            <p class="cell-sub">Keberangkatan diarsipkan. Tidak dapat menambah investor.</p>
            @endif
            @endif

        </div>
    </div>
</div>

{{-- Delete this keberangkatan --}}
<div id="tab-delete" style="display:none;">
    <div class="card">
        <div class="card-header">
            <h3 style="color:var(--danger);"><i class="bi bi-exclamation-triangle"></i> Hapus Keberangkatan</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-danger" style="padding:14px 16px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:14px;">
                <i class="bi bi-exclamation-triangle-fill"></i> Zona berbahaya. Tindakan berikut tidak dapat dibatalkan.
            </div>
            <p style="margin-bottom:16px;color:var(--text-secondary);">
                Menghapus keberangkatan ini akan menghapus <strong>secara permanen</strong> seluruh data di dalamnya:
                biaya, pendapatan, rencana/RAB, admin, dan galeri.
            </p>
            <button type="button" class="btn btn-danger" onclick="openModal('confirmDeleteStep1')">
                <i class="bi bi-trash"></i> Hapus Keberangkatan Ini
            </button>
        </div>
    </div>
</div>

{{-- Konfirmasi 1 --}}
<div class="modal-backdrop" id="confirmDeleteStep1">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3>Hapus Keberangkatan</h3>
            <button type="button" class="modal-close" onclick="closeModal('confirmDeleteStep1')">×</button>
        </div>
        <div class="modal-body">
            <p style="font-size:14px;line-height:1.6;">Apakah yakin ingin hapus keberangkatan ini?<br>Semua data dihapus permanen.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('confirmDeleteStep1')">Batal</button>
            <button type="button" class="btn btn-danger" onclick="proceedDeleteStep2()"><i class="bi bi-arrow-right"></i> Lanjut</button>
        </div>
    </div>
</div>

{{-- Konfirmasi 2 (countdown 10 detik) --}}
<div class="modal-backdrop" id="confirmDeleteStep2">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3>Konfirmasi Terakhir</h3>
            <button type="button" class="modal-close" onclick="cancelDeleteCountdown()">×</button>
        </div>
        <div class="modal-body">
            <p style="font-size:14px;line-height:1.6;margin-bottom:12px;">Apakah kamu benar-benar yakin?<br><strong>Aksi ini tidak bisa di-undo.</strong></p>
            <div style="font-size:13px;color:var(--text-secondary);">Tombol hapus aktif otomatis dalam <strong id="deleteCountdown" style="color:var(--danger);">10</strong> detik.</div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="cancelDeleteCountdown()">Batalkan</button>
            <button type="button" class="btn btn-danger" id="btnConfirmDelete" disabled><i class="bi bi-trash"></i> Hapus Permanen</button>
        </div>
        <form id="deleteProjectForm" action="{{ route('cost-centers.delete', $project->id_project) }}" method="POST" style="display:none;">@csrf</form>
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
                        <input type="text" class="form-input calc-total" name="total" data-money placeholder="0" readonly>
                    </div>
                    <div class="form-hint">Otomatis dihitung dari Qty × Harga. Bisa diubah manual.</div>
                </div>
                <div class="form-row-3" style="align-items:end;border-top:1px dashed var(--border);padding-top:12px;background:var(--primary-light);border-radius:var(--radius-sm);padding:12px;">
                    <div class="form-group">
                        <label class="form-label">Mata Uang</label>
                        <select class="form-select" name="mata_uang" id="costMataUang">
                            <option value="IDR">IDR — Rupiah</option>
                            <option value="USD">USD — Dolar AS</option>
                            <option value="SAR">SAR — Riyal</option>
                        </select>
                    </div>
                    <div class="form-group valas-only" style="display:none;">
                        <label class="form-label">Jumlah Valas</label>
                        <input type="text" class="form-input valas-amount" name="amount_valas" data-money placeholder="0">
                    </div>
                    <div class="form-group valas-only" style="display:none;">
                        <label class="form-label">Kurs (Rp/1)</label>
                        <div class="input-prefix"><span>Rp</span>
                            <input type="text" class="form-input valas-kurs" name="kurs" data-money placeholder="0"
                                   data-usd="{{ $kursUsd ?? '' }}" data-sar="{{ $kursSar ?? '' }}">
                        </div>
                    </div>
                    <div class="form-group valas-only" style="display:none;">
                        <label class="form-label">Sumber Kurs</label>
                        <select class="form-select" name="kurs_sumber">
                            <option value="bi" selected>Kurs BI</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                </div>
                <div class="valas-only" style="display:none;margin:-6px 0 12px;font-size:12px;color:var(--text-secondary);">
                    Kurs BI terdekat — USD: Rp {{ $kursUsd ? number_format($kursUsd, 2, ',', '.') : '—' }} · SAR: Rp {{ $kursSar ? number_format($kursSar, 2, ',', '.') : '—' }}. Total Rupiah = Jumlah valas × kurs.
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
                        <input type="text" class="form-input calc-total" name="total" data-money placeholder="0" readonly>
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
                        <input type="number" class="form-input calc-qty" name="qty" step="0.01" min="0.01" value="{{ $cost->qty }}" required>
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
                            <input type="text" class="form-input calc-price" name="harga_satuan" data-money value="{{ $cost->harga_satuan ? number_format($cost->harga_satuan, 0, ',', '.') : '' }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Total</label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input calc-total" name="total" data-money value="{{ $cost->total ? number_format($cost->total, 0, ',', '.') : '' }}" readonly>
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
                        <input type="number" class="form-input calc-qty" name="qty" step="0.01" min="0.01" value="{{ $income->qty }}" required>
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
                            <input type="text" class="form-input calc-price" name="harga_satuan" data-money value="{{ $income->harga_satuan ? number_format($income->harga_satuan, 0, ',', '.') : '' }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Total</label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input calc-total" name="total" data-money value="{{ $income->total ? number_format($income->total, 0, ',', '.') : '' }}" readonly>
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


{{-- Modal Unggah Bukti Transaksi --}}
@if(!$isArchived)
<div class="modal-backdrop" id="entryGalleryModal">
    <div class="modal modal-md">
        <form method="POST" enctype="multipart/form-data" id="entryGalleryForm">
            @csrf
            <div class="modal-header">
                <h3><i class="bi bi-cloud-upload"></i> Unggah Bukti</h3>
                <button type="button" class="modal-close" onclick="closeModal('entryGalleryModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">File <span style="color:var(--danger)">*</span></label>
                    <input type="file" name="files[]" multiple
                           accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,application/pdf"
                           class="form-input" required>
                    <div class="form-hint">Bisa pilih banyak file sekaligus. Foto: jpg/png/webp (maks 5MB) · Video: mp4/mov (maks 50MB) · PDF (maks 10MB)</div>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-input" placeholder="Default: Bukti Biaya / Bukti Pendapatan" maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="caption" class="form-input" placeholder="Opsional" maxlength="500">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('entryGalleryModal')">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnUploadEntryGallery"><i class="bi bi-cloud-upload"></i> Unggah</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
.bukti-wrap { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.bukti-item { position:relative; display:inline-flex; }
.bukti-item img { width:34px; height:34px; object-fit:cover; border-radius:4px; border:1px solid var(--border); }
.bukti-icon { display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:4px; border:1px solid var(--border); font-size:16px; }
.bukti-icon.video { color:#3b82f6; background:#eff6ff; }
.bukti-icon.doc { color:#ef4444; background:#fef2f2; }
.bukti-del { position:absolute; top:-6px; right:-6px; margin:0; }
.bukti-del button { width:16px; height:16px; font-size:9px; line-height:1; padding:0; border-radius:50%; background:var(--danger,#ef4444); color:#fff; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; }
</style>
@endpush

@push('scripts')
<script>
function showTab(tab, el) {
    ['costs','incomes','plans','admins','investor','delete'].forEach(t => {
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
    history.replaceState(null, '', location.pathname + '#' + tab);
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
document.querySelectorAll('form').forEach(f => {
    if (f.querySelector('.calc-qty') && !f.dataset.calcBound) {
        f.dataset.calcBound = '1';
        bindCalc(f);
    }
});

// ---- Valas (USD/SAR) pada form biaya ----
(function () {
    const costForm = document.getElementById('costForm');
    if (!costForm) return;
    const sel = costForm.querySelector('select[name="mata_uang"]');
    if (!sel) return;
    const only = costForm.querySelectorAll('.valas-only');
    const amount = costForm.querySelector('.valas-amount');
    const kurs = costForm.querySelector('.valas-kurs');
    const qty = costForm.querySelector('.calc-qty');
    const price = costForm.querySelector('.calc-price');
    const total = costForm.querySelector('.calc-total');
    const usdRate = Number(kurs?.dataset?.usd || '') || 0;
    const sarRate = Number(kurs?.dataset?.sar || '') || 0;

    function apply() {
        const isValas = sel.value !== 'IDR';
        only.forEach(el => { el.style.display = isValas ? '' : 'none'; });
        if (!isValas) {
            if (qty) qty.disabled = false;
            if (price) price.disabled = false;
            return;
        }
        if (qty) { qty.value = '1'; qty.readOnly = true; }
        if (price) { price.readOnly = true; }
        // auto-fill kurs terdekat jika kosong
        if (kurs && !parseMoney(kurs.value)) {
            const rate = sel.value === 'USD' ? usdRate : sarRate;
            if (rate > 0) { kurs.value = Math.round(rate).toLocaleString('id-ID'); }
        }
        updateTotal();
    }
    function updateTotal() {
        if (!isActive()) return;
        const a = parseMoney(amount?.value);
        const k = parseMoney(kurs?.value);
        const t = a * k;
        if (total) {
            total.value = t ? Math.round(t).toLocaleString('id-ID') : '';
        }
    }
    function isActive() { return sel.value !== 'IDR'; }
    sel.addEventListener('change', apply);
    amount?.addEventListener('input', updateTotal);
    kurs?.addEventListener('input', updateTotal);
    // saat valas, hindari bindCalc menimpa total via qty/harga
    if (qty) qty.addEventListener('input', () => { if (isActive()) updateTotal(); });
    if (price) price.addEventListener('input', () => { if (isActive()) updateTotal(); });
    apply();
})();

function filterEntries() {
    const q = (document.getElementById('entrySearch')?.value || '').toLowerCase().trim();
    const activeTab = document.querySelector('.tab.active')?.dataset.tab || 'costs';
    const table = document.getElementById(activeTab === 'costs' ? 'costTable' : 'incomeTable');
    table?.querySelectorAll('tbody tr[data-search]').forEach(row => {
        row.style.display = !q || row.dataset.search.includes(q) ? '' : 'none';
    });
}
document.getElementById('entrySearch')?.addEventListener('input', filterEntries);

let deleteCountdownTimer = null;
function proceedDeleteStep2() {
    closeModal('confirmDeleteStep1');
    const btn = document.getElementById('btnConfirmDelete');
    if (deleteCountdownTimer) clearInterval(deleteCountdownTimer);
    btn.disabled = true;
    let left = 10;
    const label = document.getElementById('deleteCountdown');
    if (label) label.textContent = left;
    deleteCountdownTimer = setInterval(() => {
        left--;
        if (label) label.textContent = left;
        if (left <= 0) {
            clearInterval(deleteCountdownTimer);
            deleteCountdownTimer = null;
            btn.disabled = false;
        }
    }, 1000);
    openModal('confirmDeleteStep2');
}
function cancelDeleteCountdown() {
    if (deleteCountdownTimer) clearInterval(deleteCountdownTimer);
    deleteCountdownTimer = null;
    closeModal('confirmDeleteStep2');
}
document.getElementById('btnConfirmDelete')?.addEventListener('click', () => {
    document.getElementById('deleteProjectForm')?.submit();
});

// Restore tab from URL hash (e.g. after form submit redirect)
(function() {
    const hash = location.hash.replace('#', '');
    const valid = ['costs','incomes','plans','admins','investor','delete'];
    if (valid.includes(hash)) {
        const btn = document.querySelector(`.tab[data-tab="${hash}"]`);
        if (btn) showTab(hash, btn);
    }
})();

// ---- Unggah bukti transaksi (multi-file, ter-link ke cost/income) ----
let entryGalleryTarget = null;
function openEntryGallery(projectId, type, entryId) {
    entryGalleryTarget = { projectId, type, entryId };
    const form = document.getElementById('entryGalleryForm');
    if (form) form.reset();
    openModal('entryGalleryModal');
}
document.getElementById('entryGalleryForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!entryGalleryTarget) return;
    const prefix = location.pathname.split('/')[1];
    const url = `/${prefix}/${entryGalleryTarget.projectId}/${entryGalleryTarget.type}/${entryGalleryTarget.entryId}/gallery`;
    const btn = document.getElementById('btnUploadEntryGallery');
    if (btn) { btn.disabled = true; btn.textContent = 'Mengunggah...'; }
    fetch(url, { method: 'POST', body: new FormData(this) })
        .then(r => r.json().catch(() => ({ message: 'Gagal mengunggah.' })))
        .then(() => { closeModal('entryGalleryModal'); location.reload(); })
        .catch(() => { closeModal('entryGalleryModal'); location.reload(); })
        .finally(() => { if (btn) { btn.disabled = false; btn.textContent = 'Unggah'; } });
});
</script>
@endpush
