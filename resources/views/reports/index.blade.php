@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Laporan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Laporan</h2>
        <p>P&L ringkas · filter periode & unit · export CSV</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline" href="{{ route('reports.export', request()->query()) }}"><i class="bi bi-download"></i> Export CSV</a>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}" class="form-row" style="align-items:end;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Dari</label>
                <input type="date" class="form-input" name="from" value="{{ $from }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Sampai</label>
                <input type="date" class="form-input" name="to" value="{{ $to }}">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Mode</label>
                <select class="form-select" name="mode">
                    <option value="">Semua</option>
                    <option value="project" @selected($mode === 'project')>Proyek</option>
                    <option value="umkm" @selected($mode === 'umkm')>UMKM</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Unit</label>
                <select class="form-select" name="project_id">
                    <option value="">Semua unit</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id_project }}" @selected((string)$projectId === (string)$u->id_project)>{{ $u->nama_project }} ({{ $u->mode_label }})</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </form>
    </div>
</div>

<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="kpi-card">
        <div class="kpi-label">Total Pendapatan</div>
        <div class="kpi-value money positive">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Total Biaya</div>
        <div class="kpi-value money negative">Rp {{ number_format($totalCost, 0, ',', '.') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Margin</div>
        <div class="kpi-value money {{ $margin >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($margin, 0, ',', '.') }}</div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:16px;">
    <div class="card">
        <div class="card-header"><h3>Biaya per kategori</h3></div>
        <div class="card-body compact">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Kategori</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse($byCostCategory as $cat => $amt)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $cat)) }}</td>
                                <td class="text-end money negative">Rp {{ number_format($amt, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2"><div class="empty-state" style="padding:20px;">Tidak ada data</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Pendapatan per kategori</h3></div>
        <div class="card-body compact">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Kategori</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse($byIncomeCategory as $cat => $amt)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $cat)) }}</td>
                                <td class="text-end money positive">Rp {{ number_format($amt, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2"><div class="empty-state" style="padding:20px;">Tidak ada data</div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>Per unit</h3></div>
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Mode</th>
                        <th class="text-end">Pendapatan</th>
                        <th class="text-end">Biaya</th>
                        <th class="text-end">Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byUnit as $row)
                        <tr class="clickable" onclick="location.href='{{ route('cost-centers.show', $row['id']) }}'">
                            <td class="cell-title">{{ $row['nama'] }}</td>
                            <td><span class="badge badge-gray">{{ $row['mode'] }}</span></td>
                            <td class="text-end money positive">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                            <td class="text-end money negative">Rp {{ number_format($row['cost'], 0, ',', '.') }}</td>
                            <td class="text-end money {{ $row['margin'] >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($row['margin'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state">Tidak ada unit</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($dailyRows->count())
<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><h3>Snapshot harian UMKM</h3></div>
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th class="text-end">Omzet</th>
                        <th class="text-end">Kas</th>
                        <th class="text-end">Pro-rate</th>
                        <th class="text-end">Profit eko</th>
                        <th class="text-end">COGS%</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyRows as $day)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($day['date'])->format('d M Y') }}</td>
                            <td class="text-end money positive">Rp {{ number_format($day['income'], 0, ',', '.') }}</td>
                            <td class="text-end money negative">Rp {{ number_format($day['cost_cash'], 0, ',', '.') }}</td>
                            <td class="text-end">Rp {{ number_format($day['fixed_prorate'], 0, ',', '.') }}</td>
                            <td class="text-end money {{ $day['margin_economic'] >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($day['margin_economic'], 0, ',', '.') }}</td>
                            <td class="text-end">{{ $day['cogs_ratio_pct'] !== null ? number_format($day['cogs_ratio_pct'], 1).'%' : '—' }}</td>
                            <td>
                                @if($day['is_closed']) <span class="badge badge-green">Tutup</span>
                                @elseif($day['leak_alert'] || $day['over_budget']) <span class="badge badge-red">Alert</span>
                                @else <span class="badge badge-gray">Open</span> @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Detail biaya ({{ $costs->count() }})</h3></div>
        <div class="card-body compact" style="max-height:360px;overflow:auto;">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tgl</th><th>Unit</th><th>Tipe</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @foreach($costs->take(100) as $c)
                            <tr>
                                <td style="white-space:nowrap;">{{ $c->tanggal?->format('d M') }}</td>
                                <td>{{ $c->project?->nama_project }}</td>
                                <td>{{ $c->costType?->nama }}</td>
                                <td class="text-end money negative">Rp {{ number_format($c->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Detail pendapatan ({{ $incomes->count() }})</h3></div>
        <div class="card-body compact" style="max-height:360px;overflow:auto;">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Tgl</th><th>Unit</th><th>Tipe</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @foreach($incomes->take(100) as $i)
                            <tr>
                                <td style="white-space:nowrap;">{{ $i->tanggal?->format('d M') }}</td>
                                <td>{{ $i->project?->nama_project }}</td>
                                <td>{{ $i->incomeType?->nama }}</td>
                                <td class="text-end money positive">Rp {{ number_format($i->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
