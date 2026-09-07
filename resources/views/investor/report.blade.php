@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('investor.index') }}">{{ $project->nama_project }}</a>
    <span class="sep">/</span>
    <span class="current">Laporan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Laporan</h2>
        <p>Ringkasan biaya & pendapatan per kategori dalam periode.</p>
    </div>
    <div class="page-actions">
        <form method="GET" action="{{ route('investor.report') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Dari</label>
                <input type="date" class="form-input" name="from" value="{{ $from }}" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Sampai</label>
                <input type="date" class="form-input" name="to" value="{{ $to }}" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Tampilkan</button>
        </form>
    </div>
</div>

<p style="color:var(--text-secondary);font-size:13.5px;">
    Periode: {{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
</p>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-arrow-up-circle"></i></div></div>
        <div class="kpi-label">Total Pendapatan</div>
        <div class="kpi-value money positive">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-arrow-down-circle"></i></div></div>
        <div class="kpi-label">Total Biaya</div>
        <div class="kpi-value money negative">Rp {{ number_format($totalCost, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-graph-up-arrow"></i></div></div>
        <div class="kpi-label">Margin</div>
        <div class="kpi-value money {{ $margin >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($margin, 0, ',', '.') }}</div>
    </div>
</div>

<div class="grid-2" style="margin-top:22px;">
    <div class="card">
        <div class="card-header"><h3>Biaya per Kategori</h3></div>
        <div class="card-body compact">
            @if($byCost->isEmpty())
            <div class="empty-state"><i class="bi bi-inbox"></i><p>Tidak ada data</p></div>
            @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Kategori</th><th class="text-end">Total</th><th class="text-end">Persentase</th></tr></thead>
                    <tbody>
                        @foreach($byCost as $label => $amount)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="text-end money negative">Rp {{ number_format($amount, 0, ',', '.') }}</td>
                            <td class="text-end cell-sub">{{ $totalCost > 0 ? number_format(($amount / $totalCost) * 100, 1, ',', '.') : 0 }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td class="text-end money negative"><strong>Rp {{ number_format($totalCost, 0, ',', '.') }}</strong></td>
                            <td class="text-end cell-sub">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Pendapatan per Kategori</h3></div>
        <div class="card-body compact">
            @if($byIncome->isEmpty())
            <div class="empty-state"><i class="bi bi-inbox"></i><p>Tidak ada data</p></div>
            @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Kategori</th><th class="text-end">Total</th><th class="text-end">Persentase</th></tr></thead>
                    <tbody>
                        @foreach($byIncome as $label => $amount)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="text-end money positive">Rp {{ number_format($amount, 0, ',', '.') }}</td>
                            <td class="text-end cell-sub">{{ $totalIncome > 0 ? number_format(($amount / $totalIncome) * 100, 1, ',', '.') : 0 }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>Total</strong></td>
                            <td class="text-end money positive"><strong>Rp {{ number_format($totalIncome, 0, ',', '.') }}</strong></td>
                            <td class="text-end cell-sub">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
