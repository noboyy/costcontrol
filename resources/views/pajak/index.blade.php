@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Perkiraan Pajak</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Perkiraan Pajak</h2>
        <p>Estimasi PPh berdasarkan mode pajak perusahaan · {{ $label }}</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('perusahaan.index') }}" class="btn btn-outline"><i class="bi bi-gear"></i> Atur Mode & Tarif</a>
    </div>
</div>

@if(!$pajakAktif)
    <div class="alert alert-info" style="margin-bottom:16px;">
        <i class="bi bi-info-circle"></i> Perkiraan pajak sedang nonaktif. Aktifkan di <a href="{{ route('perusahaan.index') }}">Pengaturan</a>.
    </div>
@endif

<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="GET" action="{{ route('pajak.index') }}" class="form-row" style="align-items:end;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Periode</label>
                <select class="form-select" name="periode" onchange="this.form.submit()">
                    <option value="bulan" @selected($periode === 'bulan')>Bulan</option>
                    <option value="tahun" @selected($periode === 'tahun')>Tahun</option>
                    <option value="keberangkatan" @selected($periode === 'keberangkatan')>Per Keberangkatan</option>
                </select>
            </div>

            @if($periode === 'bulan')
            <div class="form-group" style="margin:0;">
                <label class="form-label">Bulan</label>
                <input type="month" class="form-input" name="bulan" value="{{ request('bulan', now()->format('Y-m')) }}">
            </div>
            @endif

            @if($periode === 'tahun')
            <div class="form-group" style="margin:0;">
                <label class="form-label">Tahun</label>
                <select class="form-select" name="tahun">
                    @forelse($years as $y)
                        <option value="{{ $y }}" @selected((int) request('tahun', now()->year) === (int) $y)>{{ $y }}</option>
                    @empty
                        <option value="{{ now()->year }}">{{ now()->year }}</option>
                    @endforelse
                </select>
            </div>
            @endif

            @if($periode === 'keberangkatan')
            <div class="form-group" style="margin:0;min-width:220px;">
                <label class="form-label">Keberangkatan</label>
                <select class="form-select" name="project_id">
                    <option value="">Semua keberangkatan</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id_project }}" @selected((string) $projectId === (string) $p->id_project)>{{ $p->nama_project }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Tampilkan</button>
        </form>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-cash-stack"></i></div></div>
        <div class="kpi-label">Omzet (Pendapatan)</div>
        <div class="kpi-value">Rp {{ number_format($omzet, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">{{ $label }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-arrow-down-circle"></i></div></div>
        <div class="kpi-label">Biaya (Proyek + Umum)</div>
        <div class="kpi-value">Rp {{ number_format($costProject + $costUmum, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">Proyek {{ number_format($costProject, 0, ',', '.') }} · Umum {{ number_format($costUmum, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon {{ $laba >= 0 ? 'blue' : 'red' }}"><i class="bi bi-graph-up-arrow"></i></div></div>
        <div class="kpi-label">Laba (Estimasi)</div>
        <div class="kpi-value money {{ $laba >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($laba, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">Income − biaya</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon yellow"><i class="bi bi-receipt"></i></div></div>
        <div class="kpi-label">Estimasi Pajak</div>
        <div class="kpi-value">Rp {{ number_format($pajak, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">{{ $dasarLabel }} × {{ rtrim(rtrim(number_format($tarif, 2, '.', ''), '0'), '.') }}%</div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header">
        <h3><i class="bi bi-list-ul"></i> Rincian per Keberangkatan</h3>
    </div>
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Keberangkatan</th>
                        <th class="text-end">Omzet</th>
                        <th class="text-end">Biaya</th>
                        <th class="text-end">Laba</th>
                        <th class="text-end">Estimasi Pajak</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td class="cell-title">{{ $r['nama_project'] }}</td>
                            <td class="text-end money positive">Rp {{ number_format($r['omzet'], 0, ',', '.') }}</td>
                            <td class="text-end money negative">Rp {{ number_format($r['cost'], 0, ',', '.') }}</td>
                            <td class="text-end money {{ $r['laba'] >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($r['laba'], 0, ',', '.') }}</td>
                            <td class="text-end money">Rp {{ number_format($r['pajak'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state">Tidak ada keberangkatan aktif</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($mode === 'badan_laba')
<div class="alert" style="margin-bottom:16px;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>Mode "PPh Badan dari Laba": laba di sini adalah laba akuntansi (pendapatan − biaya), bukan laba fiskal (setelah koreksi fiskal & peraturan perpajakan). Angka ini hanya perkiraan — konsultasikan dengan akuntan/perpajakan.</div>
</div>
@else
<div class="alert" style="margin-bottom:16px;">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>Estimasi PPh Final 0,5% dari omzet (PP 55/2022). Pastikan perusahaan memenuhi syarat UMKM (omzet ≤ 4,8 M/thn), memiliki SKet, setor pajak final bulanan, dan laporkan di SPT Tahunan.</div>
</div>
@endif
@endsection
