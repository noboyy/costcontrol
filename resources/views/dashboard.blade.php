@extends('layouts.app')

@section('breadcrumb')
    <span class="current">Dashboard</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Halo, {{ explode(' ', auth()->user()->nama_lengkap ?? 'Admin')[0] }}</h2>
        <p>Ringkasan keberangkatan · {{ now()->translatedFormat('l, d F Y') }}
            · <strong>{{ $countProject ?? 0 }}</strong> keberangkatan
        </p>
    </div>
    <div class="page-actions">
        <a href="{{ route('cost-centers.index') }}" class="btn btn-outline"><i class="bi bi-airplane"></i> Keberangkatan</a>
        <button class="btn btn-primary" onclick="location.href='{{ route('cost-centers.index') }}#new'"><i class="bi bi-plus-lg"></i> Keberangkatan Baru</button>
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
        <div class="kpi-change neutral"><i class="bi bi-cash-stack"></i> Semua keberangkatan aktif</div>
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

<div class="card" style="margin-bottom:18px;">
    <div class="card-header">
        <h3><i class="bi bi-wallet2"></i> Kas Perusahaan</h3>
        <a href="{{ route('general-expenses.index') }}" class="btn btn-sm btn-outline"><i class="bi bi-plus"></i> Catat Pengeluaran Umum</a>
    </div>
    <div class="card-body">
        <div class="kpi-grid">
            <div>
                <div class="kpi-label">Saldo Kas Global</div>
                <div class="kpi-value money {{ ($companyPosition['balance'] ?? 0) < 0 ? 'negative' : 'positive' }}" style="font-size:20px;">
                    Rp {{ number_format($companyPosition['balance'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="kpi-change neutral">Awal + pemasukan − biaya (semua keberangkatan & umum)</div>
            </div>
            <div>
                <div class="kpi-label">Pemasukan bulan ini</div>
                <div class="kpi-value money positive">Rp {{ number_format($companySummary['income'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="kpi-label">Biaya bulan ini</div>
                <div class="kpi-value money negative">Rp {{ number_format($companySummary['cost'] ?? 0, 0, ',', '.') }}</div>
                <div class="kpi-change neutral">
                    Umum: Rp {{ number_format($companySummary['cost_general'] ?? 0, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
</div>


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
                                <td>
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

<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-airplane"></i> Keberangkatan Aktif</h3>
        <a href="{{ route('cost-centers.index') }}" class="btn btn-sm btn-outline">Lihat semua</a>
    </div>
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Keberangkatan</th>
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
                                    <p>Belum ada keberangkatan aktif</p>
                                    <a href="{{ route('cost-centers.index') }}#new">Buat keberangkatan</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
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
