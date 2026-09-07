@extends('layouts.investor')

@section('title', $project->nama_project)

@section('content')
@php
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $todayMargin = $todayIncome - $todayCost;
@endphp

<div class="head">
    <div class="head-row">
        <div>
            <h1 class="h1">{{ $project->nama_project }}</h1>
            <div class="meta">
                @if($project->client)<span>{{ $project->client }}</span>@endif
                @if($project->lokasi)<span class="sep">·</span><span>{{ $project->lokasi }}</span>@endif
                @if($project->date_start)
                <span class="sep">·</span>
                <span>{{ $project->date_start->format('d M Y') }}@if($project->date_end) — {{ $project->date_end->format('d M Y') }}@endif</span>
                @endif
            </div>
        </div>
        @if($project->status === 'archived')
        <span class="status status-arch"><i class="bi bi-archive"></i> Diarsipkan</span>
        @else
        <span class="status status-live"><i class="bi bi-dot"></i> Berjalan</span>
        @endif
    </div>
</div>

@if($empty)
<div class="alertb alertb-info">
    <i class="bi bi-info-circle"></i>
    <span>Belum ada transaksi tercatat. Saldo kas dan ringkasan akan muncul setelah admin mencatat biaya & pendapatan.</span>
</div>
@endif

@if($cash['is_negative'])
<div class="alertb alertb-danger">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Saldo kas negatif — pengeluaran telah melebihi pemasukan.</span>
</div>
@endif

<div class="grid-hero">
    <div class="card">
        <div class="hero-label">Saldo Kas Berjalan</div>
        <div class="hero-num {{ $cash['balance'] < 0 ? 'neg' : 'pos' }}">{{ $rp($cash['balance']) }}</div>
        <div class="hero-sub">per {{ \Carbon\Carbon::parse($cash['date'])->format('d M Y') }}</div>
        <div class="subtle-note">Saldo awal + pemasukan − biaya</div>
    </div>
    <div class="card">
        <div class="hero-label">Ringkasan</div>
        <div style="margin-top:10px;">
            <div class="kv kv-line">
                <span class="k">Total Pendapatan</span>
                <span class="v pos">{{ $rp($totalIncome) }}</span>
            </div>
            <div class="kv">
                <span class="k">Total Biaya</span>
                <span class="v neg">{{ $rp($totalCost) }}</span>
            </div>
        </div>
    </div>
</div>

@if($project->project_value && !$empty)
<div class="card" style="margin-top:16px;">
    @php
        $pctUsed = $project->project_value > 0 ? ($totalCost / $project->project_value) * 100 : 0;
        $remaining = (float) $project->project_value - $totalCost;
        $barColor = $pctUsed > 100 ? 'var(--down)' : ($pctUsed > 80 ? 'var(--amber)' : 'var(--accent)');
    @endphp
    <div class="budget-meta">
        <span>Realisasi anggaran</span>
        <strong>{{ $rp($totalCost) }} <span class="muted" style="font-weight:500;">dari {{ $rp($project->project_value) }}</span> · sisa {{ $rp(max(0,$remaining)) }}</strong>
    </div>
    <div class="track"><i style="width:{{ min(100,$pctUsed) }}%;background:{{ $barColor }};"></i></div>
    <div class="hero-sub" style="text-align:right;">{{ number_format(min(100,$pctUsed), 1, ',', '.') }}% terpakai</div>
</div>
@endif

@if($project->project_value && $totalCost > $project->project_value)
<div class="alertb alertb-danger" style="margin-top:16px;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span>Biaya melebihi nilai proyek sebesar <strong>{{ $rp($totalCost - $project->project_value) }}</strong>.</span>
</div>
@endif

@if(!$empty)
<section class="sec">
    <div class="sec-title">Bulan Ini</div>
    <div class="cols-3">
        <div class="card">
            <div class="mini-label">Pemasukan</div>
            <div class="mini-val pos">{{ $rp($monthIncome) }}</div>
        </div>
        <div class="card">
            <div class="mini-label">Pengeluaran</div>
            <div class="mini-val neg">{{ $rp($monthCost) }}</div>
        </div>
        <div class="card">
            <div class="mini-label">Margin Hari Ini</div>
            <div class="mini-val {{ $todayMargin >= 0 ? 'pos' : 'neg' }}">{{ $rp($todayMargin) }}</div>
        </div>
    </div>
</section>
@endif

@if($byCost->isNotEmpty() || $byIncome->isNotEmpty())
<section class="sec">
    <div class="sec-title">Per Kategori</div>
    <div class="cols-2">
        @if($byCost->isNotEmpty())
        <div class="card">
            <div class="hero-label" style="margin-bottom:6px;">Biaya</div>
            @php $maxC = max(1, $byCost->max()); @endphp
            @foreach($byCost as $label => $amount)
            <div class="brow">
                <span class="bname">{{ $label }}</span>
                <span class="bamt neg">{{ $rp($amount) }} <small>· {{ $totalCost > 0 ? number_format(($amount/$totalCost)*100,0) : 0 }}%</small></span>
                <div class="bbar"><i style="width:{{ ($amount/$maxC)*100 }}%;background:var(--down);"></i></div>
            </div>
            @endforeach
        </div>
        @endif
        @if($byIncome->isNotEmpty())
        <div class="card">
            <div class="hero-label" style="margin-bottom:6px;">Pendapatan</div>
            @php $maxI = max(1, $byIncome->max()); @endphp
            @foreach($byIncome as $label => $amount)
            <div class="brow">
                <span class="bname">{{ $label }}</span>
                <span class="bamt pos">{{ $rp($amount) }} <small>· {{ $totalIncome > 0 ? number_format(($amount/$totalIncome)*100,0) : 0 }}%</small></span>
                <div class="bbar"><i style="width:{{ ($amount/$maxI)*100 }}%;background:var(--up);"></i></div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif
@endsection
