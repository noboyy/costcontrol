@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">{{ $project->nama_project }}</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>{{ $project->nama_project }}</h2>
        <p>
            @if($project->client)<span>Klien: {{ $project->client }}</span>@endif
            @if($project->lokasi)<span> · Lokasi: {{ $project->lokasi }}</span>@endif
            @if($project->date_start)<span> · {{ $project->date_start->format('d M Y') }}@if($project->date_end) – {{ $project->date_end->format('d M Y') }}@endif</span>@endif
            @if($project->status === 'archived') · <span class="badge badge-gray">Arsip</span>@endif
        </p>
    </div>
</div>

@if($empty)
<div class="alert alert-info" style="margin-bottom:16px;">
    <i class="bi bi-info-circle"></i>
    <span>Belum ada transaksi. Data biaya dan pendapatan akan muncul setelah admin mencatatnya.</span>
</div>
@endif

@if($cash['is_negative'])
<div class="alert alert-danger" style="margin-bottom:16px;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Saldo kas negatif. Pengeluaran melebihi pemasukan — segera tinjau arus kas.</span>
</div>
@endif

@if($project->project_value && $totalCost > $project->project_value)
<div class="alert alert-danger" style="margin-bottom:16px;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Biaya <strong>Rp {{ number_format($totalCost - $project->project_value, 0, ',', '.') }}</strong> melebihi nilai kontrak.</span>
</div>
@endif

{{-- Ringkasan --}}
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon green"><i class="bi bi-arrow-up-circle"></i></div>
        </div>
        <div class="kpi-label">Total Pendapatan</div>
        <div class="kpi-value money positive">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon red"><i class="bi bi-arrow-down-circle"></i></div>
        </div>
        <div class="kpi-label">Total Biaya</div>
        <div class="kpi-value money negative">Rp {{ number_format($totalCost, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
        <div class="kpi-label">Margin</div>
        <div class="kpi-value money {{ $margin >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($margin, 0, ',', '.') }}</div>
        <div class="kpi-change {{ $margin >= 0 ? 'up' : 'down' }}">
            {{ $totalIncome > 0 ? 'Margin '.number_format(($margin / $totalIncome) * 100, 1, ',', '.').'%' : 'Margin —%' }}
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon slate"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="kpi-label">{{ $project->project_value ? 'Nilai Keberangkatan' : 'Pendapatan Hari Ini' }}</div>
        <div class="kpi-value money">
            Rp {{ number_format($project->project_value ?? $todayIncome, 0, ',', '.') }}
        </div>
    </div>
</div>

{{-- Kas berjalan --}}
<div class="card" style="margin-top:18px;">
    <div class="card-header"><h3><i class="bi bi-wallet2"></i> Kas Berjalan</h3></div>
    <div class="card-body">
        <div class="kpi-grid">
            <div>
                <div class="kpi-label">Saldo Kas Hari Ini</div>
                <div class="kpi-value money {{ $cash['balance'] < 0 ? 'negative' : 'positive' }}" style="font-size:22px;">
                    Rp {{ number_format($cash['balance'], 0, ',', '.') }}
                </div>
                <div class="kpi-change neutral">Per {{ \Carbon\Carbon::parse($cash['date'])->format('d M Y') }}</div>
            </div>
            <div>
                <div class="kpi-label">Saldo Awal</div>
                <div class="kpi-value money">Rp {{ number_format($cash['opening'], 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="kpi-label">Pemasukan</div>
                <div class="kpi-value money positive">Rp {{ number_format($cash['income_to_date'], 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="kpi-label">Pengeluaran</div>
                <div class="kpi-value money negative">Rp {{ number_format($cash['cost_to_date'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

@if($project->project_value)
<div class="card" style="margin-top:18px;">
    <div class="card-header"><h3><i class="bi bi-bullseye"></i> Realisasi Anggaran</h3></div>
    <div class="card-body">
        @php
            $usedPct = $project->project_value > 0 ? min(100, ($totalCost / $project->project_value) * 100) : 0;
            $barColor = $usedPct > 100 ? '#ef4444' : ($usedPct > 80 ? '#f59e0b' : '#10b981');
        @endphp
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
            <span style="color:var(--text-secondary);">Biaya terpakai</span>
            <strong>Rp {{ number_format($totalCost, 0, ',', '.') }} <span style="color:var(--text-secondary);font-weight:400;">/ Rp {{ number_format($project->project_value, 0, ',', '.') }}</span></strong>
        </div>
        <div class="progress">
            <div class="progress-bar" style="width:{{ $usedPct }}%;background:{{ $barColor }};"></div>
        </div>
        <div style="margin-top:6px;text-align:right;font-size:13px;font-weight:600;">{{ number_format($usedPct, 1, ',', '.') }}% terpakai</div>
    </div>
</div>
@endif

{{-- Breakdown per kategori --}}
@if($byCost->isNotEmpty() || $byIncome->isNotEmpty())
<div style="margin-top:22px;">
    <h3 style="margin:0 0 12px;font-size:15px;">Breakdown Per Kategori</h3>
    <div class="grid-2">
        @if($byCost->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3>Biaya</h3></div>
            <div class="card-body compact">
                @foreach($byCost as $label => $amount)
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border);font-size:13.5px;">
                    <span>{{ $label }}</span>
                    <span class="money negative">Rp {{ number_format($amount, 0, ',', '.') }}<span style="color:var(--text-secondary);"> · {{ $totalCost > 0 ? number_format(($amount / $totalCost) * 100, 0) : 0 }}%</span></span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @if($byIncome->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3>Pendapatan</h3></div>
            <div class="card-body compact">
                @foreach($byIncome as $label => $amount)
                <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border);font-size:13.5px;">
                    <span>{{ $label }}</span>
                    <span class="money positive">Rp {{ number_format($amount, 0, ',', '.') }}<span style="color:var(--text-secondary);"> · {{ $totalIncome > 0 ? number_format(($amount / $totalIncome) * 100, 0) : 0 }}%</span></span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- Bulan ini --}}
<div style="margin-top:22px;">
    <h3 style="margin:0 0 12px;font-size:15px;">Bulan Ini</h3>
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-arrow-up-circle"></i></div></div>
            <div class="kpi-label">Pendapatan Bulan Ini</div>
            <div class="kpi-value money positive">Rp {{ number_format($monthIncome, 0, ',', '.') }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-arrow-down-circle"></i></div></div>
            <div class="kpi-label">Biaya Bulan Ini</div>
            <div class="kpi-value money negative">Rp {{ number_format($monthCost, 0, ',', '.') }}</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-graph-up-arrow"></i></div></div>
            <div class="kpi-label">Margin Hari Ini</div>
            <div class="kpi-value money {{ ($todayIncome - $todayCost) >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($todayIncome - $todayCost, 0, ',', '.') }}</div>
        </div>
    </div>
</div>
@endsection
