@extends('layouts.app')

@section('breadcrumb')
    <span class="current">Dashboard</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Halo, {{ explode(' ', auth()->user()->nama_lengkap ?? 'Admin')[0] }}</h2>
        <p>Ringkasan multi-bisnis · {{ now()->translatedFormat('l, d F Y') }}
            @if($module !== 'umkm')
            · <strong>{{ $countProject ?? 0 }}</strong> proyek
            @endif
            @if($module !== 'project')
            · <strong>{{ $countUmkm ?? 0 }}</strong> UMKM
            @endif
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('cost-centers.index') }}" class="btn btn-outline"><i class="bi bi-building"></i> Unit Bisnis</a>
        @if($module !== 'project')
        <button class="btn btn-outline" onclick="location.href='{{ route('cost-centers.index') }}#umkm'"><i class="bi bi-shop"></i> + UMKM</button>
        @endif
        <button class="btn btn-primary" onclick="location.href='{{ route('cost-centers.index') }}#new'"><i class="bi bi-plus-lg"></i> Unit Baru</button>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon red"><i class="bi bi-arrow-down-circle"></i></div>
        </div>
        <div class="kpi-label">Total Biaya</div>
        <div class="kpi-value">{{ $summaryTotal }}</div>
        <div class="kpi-change {{ str_contains($summaryChangeLabel, '+') ? 'up' : (str_contains($summaryChangeLabel, '-') ? 'down' : 'neutral') }}">
            <i class="bi bi-{{ str_contains($summaryChangeLabel, '+') ? 'arrow-up-right' : (str_contains($summaryChangeLabel, '-') ? 'arrow-down-right' : 'dash') }}"></i>
            {{ $summaryChangeLabel }} vs bulan lalu
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon green"><i class="bi bi-arrow-up-circle"></i></div>
        </div>
        <div class="kpi-label">Total Pendapatan</div>
        <div class="kpi-value">{{ $summaryBudget }}</div>
        <div class="kpi-change neutral"><i class="bi bi-cash-stack"></i> Semua unit aktif</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon blue"><i class="bi bi-pie-chart"></i></div>
        </div>
        <div class="kpi-label">Margin</div>
        <div class="kpi-value">{{ $summaryRemaining }}</div>
        <div class="kpi-change neutral">Pendapatan − Biaya</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top">
            <div class="kpi-icon yellow"><i class="bi bi-receipt"></i></div>
        </div>
        <div class="kpi-label">Transaksi</div>
        <div class="kpi-value">{{ $summaryTxCount }}</div>
        <div class="kpi-change neutral">Cost + Income entries</div>
    </div>
</div>

{{-- UMKM Hari Ini --}}
@if($module !== 'project' && (($umkmTodayTotals['count'] ?? 0) > 0 || ($countUmkm ?? 0) > 0))
<div class="card" style="margin-bottom:18px;">
    <div class="card-header">
        <h3><i class="bi bi-shop"></i> UMKM · Hari Ini</h3>
        <a href="{{ route('cost-centers.index', ['mode' => 'umkm']) }}" class="btn btn-sm btn-outline">Semua UMKM</a>
    </div>
    <div class="card-body">
        <div class="kpi-grid" style="margin-bottom:16px;grid-template-columns:repeat(3,1fr);">
            <div>
                <div class="kpi-label">Biaya hari ini</div>
                <div class="kpi-value money negative" style="font-size:18px;">Rp {{ number_format($umkmTodayTotals['cost'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="kpi-label">Omzet hari ini</div>
                <div class="kpi-value money positive" style="font-size:18px;">Rp {{ number_format($umkmTodayTotals['income'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="kpi-label">Profit hari ini</div>
                <div class="kpi-value money {{ ($umkmTodayTotals['margin'] ?? 0) >= 0 ? 'positive' : 'negative' }}" style="font-size:18px;">
                    Rp {{ number_format($umkmTodayTotals['margin'] ?? 0, 0, ',', '.') }}
                </div>
            </div>
        </div>

        @if(($umkmToday ?? collect())->count() > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Outlet</th>
                        <th class="text-end">Biaya</th>
                        <th class="text-end">Omzet</th>
                        <th class="text-end">Profit</th>
                        <th class="text-end">vs Pagu</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($umkmToday as $u)
                        <tr class="clickable" onclick="location.href='{{ route('cost-centers.show', $u['id_project']) }}'">
                            <td>
                                <div class="cell-title">
                                    {{ $u['nama_project'] }}
                                    @if($u['is_closed'] ?? false)<span class="badge badge-green" style="margin-left:4px;">Tutup</span>@endif
                                    @if(($u['leak_alert'] ?? false) || ($u['over_budget'] ?? false))
                                        <span class="badge badge-red" style="margin-left:4px;">Alert</span>
                                    @endif
                                </div>
                                @if($u['lokasi'])<div class="cell-sub">{{ $u['lokasi'] }}</div>@endif
                            </td>
                            <td class="text-end money negative">Rp {{ number_format($u['today_cost'], 0, ',', '.') }}</td>
                            <td class="text-end money positive">Rp {{ number_format($u['today_income'], 0, ',', '.') }}</td>
                            <td class="text-end money {{ $u['today_margin'] >= 0 ? 'positive' : 'negative' }}">
                                Rp {{ number_format($u['today_margin'], 0, ',', '.') }}
                                @if(($u['fixed_prorate'] ?? 0) > 0)
                                    <div class="cell-sub">Eko: Rp {{ number_format($u['margin_economic'] ?? 0, 0, ',', '.') }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($u['usage_pct'] !== null)
                                    @php $c = $u['usage_pct'] > 100 ? 'badge-red' : ($u['usage_pct'] > 80 ? 'badge-yellow' : 'badge-green'); @endphp
                                    <span class="badge {{ $c }}">{{ number_format($u['usage_pct'], 0) }}%</span>
                                @else
                                    <span class="cell-sub">—</span>
                                @endif
                            </td>
                            <td class="text-end" onclick="event.stopPropagation()">
                                <a href="{{ route('cost-centers.show', $u['id_project']) }}" class="btn btn-xs btn-outline">Catat</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <div class="empty-state" style="padding:20px;">
                <p>Belum ada unit UMKM aktif</p>
                <a href="{{ route('cost-centers.index') }}#umkm" class="btn btn-sm btn-primary">Buat unit UMKM</a>
            </div>
        @endif
    </div>
</div>
@endif

<div class="grid-2" style="margin-bottom: 20px;">
    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-graph-up"></i> Tren Biaya 7 Hari</h3>
        </div>
        <div class="card-body">
            <div style="height:220px;position:relative;">
                <canvas id="weeklyCostChart"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="bi bi-clock-history"></i> Aktivitas Terbaru</h3>
        </div>
        <div class="card-body compact">
            <div class="table-wrap">
                <table>
                    <tbody>
                        @forelse($recentActivities as $activity)
                            <tr @if(!empty($activity['project_id'])) class="clickable" onclick="location.href='{{ route('cost-centers.show', $activity['project_id']) }}'" @endif>
                                <td style="width:48px;">
                                    <div style="width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:{{ $activity['jenis'] === 'biaya' ? 'var(--danger-light)' : 'var(--success-light)' }};color:{{ $activity['jenis'] === 'biaya' ? 'var(--danger)' : 'var(--success)' }}">
                                        <i class="bi bi-{{ $activity['jenis'] === 'biaya' ? 'arrow-down' : 'arrow-up' }}"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-title">{{ $activity['keterangan'] ?: ($activity['tipe'] ?? 'Transaksi') }}</div>
                                    <div class="cell-sub">{{ $activity['tipe'] ?? '-' }} · {{ \Carbon\Carbon::parse($activity['tanggal'])->format('d M Y') }}</div>
                                </td>
                                <td class="text-end">
                                    <span class="money {{ $activity['jenis'] === 'biaya' ? 'negative' : 'positive' }}">
                                        {{ $activity['jenis'] === 'biaya' ? '−' : '+' }}Rp {{ number_format($activity['total'], 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td>
                                    <div class="empty-state" style="padding:32px;">
                                        <i class="bi bi-inbox"></i>
                                        <p>Belum ada aktivitas</p>
                                        <a href="{{ route('cost-centers.index') }}">Mulai dari unit bisnis</a>
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

@if($module !== 'umkm')
<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-building"></i> Proyek Aktif</h3>
        <a href="{{ route('cost-centers.index', ['mode' => 'project']) }}" class="btn btn-sm btn-outline">Lihat semua</a>
    </div>
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Proyek</th>
                        <th>Klien</th>
                        <th class="text-end">Biaya</th>
                        <th class="text-end">Pendapatan</th>
                        <th class="text-end">Margin</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projectSummaries as $project)
                        <tr class="clickable" onclick="location.href='{{ route('cost-centers.show', $project['id_project']) }}'">
                            <td><div class="cell-title">{{ $project['nama_project'] }}</div></td>
                            <td>{{ $project['client'] ?? '—' }}</td>
                            <td class="text-end"><span class="money negative">Rp {{ number_format($project['total_cost'], 0, ',', '.') }}</span></td>
                            <td class="text-end"><span class="money positive">Rp {{ number_format($project['total_income'], 0, ',', '.') }}</span></td>
                            <td class="text-end"><span class="money {{ $project['margin'] >= 0 ? 'positive' : 'negative' }}">Rp {{ number_format($project['margin'], 0, ',', '.') }}</span></td>
                            <td class="text-end" onclick="event.stopPropagation()">
                                <a href="{{ route('cost-centers.show', $project['id_project']) }}" class="btn btn-xs btn-outline btn-icon" title="Buka"><i class="bi bi-arrow-right"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-folder2-open"></i>
                                    <p>Belum ada proyek aktif</p>
                                    <a href="{{ route('cost-centers.index') }}#new">Buat unit proyek</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('weeklyCostChart').getContext('2d');
const weeklyCosts = @json($weeklyCosts);
const gradient = ctx.createLinearGradient(0, 0, 0, 220);
gradient.addColorStop(0, 'rgba(37,99,235,0.18)');
gradient.addColorStop(1, 'rgba(37,99,235,0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: weeklyCosts.map(i => i.label),
        datasets: [{
            data: weeklyCosts.map(i => i.value),
            borderColor: '#2563eb',
            backgroundColor: gradient,
            borderWidth: 2.5,
            tension: 0.35,
            fill: true,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#2563eb',
            pointBorderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f172a',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: (c) => 'Rp ' + Number(c.raw).toLocaleString('id-ID')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                border: { display: false },
                grid: { color: '#f1f5f9' },
                ticks: {
                    font: { size: 11, family: 'Inter' },
                    color: '#94a3b8',
                    callback: v => 'Rp ' + Number(v).toLocaleString('id-ID')
                }
            },
            x: {
                border: { display: false },
                grid: { display: false },
                ticks: { font: { size: 11, family: 'Inter' }, color: '#94a3b8' }
            }
        }
    }
});
</script>
@endpush
