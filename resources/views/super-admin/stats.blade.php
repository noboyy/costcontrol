@extends('layouts.app')

@section('breadcrumb')
    <span class="current">Owner Panel · Ringkasan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Owner Panel</h2>
        <p>Ringkasan internal {{ $overview['perusahaan'] }}.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('pengguna.index') }}" class="btn btn-outline"><i class="bi bi-people"></i> Kelola Pengguna</a>
        <a href="{{ route('perusahaan.index') }}" class="btn btn-outline"><i class="bi bi-gear"></i> Pengaturan</a>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-people"></i></div></div>
        <div class="kpi-label">Total User</div>
        <div class="kpi-value">{{ number_format($overview['total_user']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-person-check"></i></div></div>
        <div class="kpi-label">User Aktif</div>
        <div class="kpi-value">{{ number_format($overview['aktif']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon yellow"><i class="bi bi-person-x"></i></div></div>
        <div class="kpi-label">Akun Nonaktif</div>
        <div class="kpi-value">{{ number_format($overview['nonaktif']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-kanban"></i></div></div>
        <div class="kpi-label">Total Keberangkatan</div>
        <div class="kpi-value">{{ number_format($overview['proyek']) }}</div>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-person-badge"></i></div></div>
        <div class="kpi-label">Akun Admin</div>
        <div class="kpi-value">{{ number_format($overview['admin']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-person-arms-up"></i></div></div>
        <div class="kpi-label">Akun Investor</div>
        <div class="kpi-value">{{ number_format($overview['investor']) }}</div>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3>User Terbaru</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentUsers as $u)
                    <tr>
                        <td>{{ $u['nama'] }}</td>
                        <td>{{ $u['email'] }}</td>
                        <td>{{ $u['role'] }}</td>
                        <td>
                            <span class="badge {{ $u['status'] === 'aktif' ? 'badge-green' : 'badge-red' }}">
                                {{ $u['status'] }}
                            </span>
                        </td>
                        <td>{{ $u['created_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Belum ada user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px;" class="two-col">
    <div class="card">
        <div class="card-header">
            <h3>Biaya Terbaru</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Projek</th>
                        <th class="text-end">Nominal</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentCosts as $c)
                        <tr>
                            <td>{{ $c['project'] }}</td>
                            <td class="text-end">Rp {{ number_format((float) $c['nominal'], 0, ',', '.') }}</td>
                            <td>{{ $c['tanggal'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">Belum ada transaksi biaya.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Pendapatan Terbaru</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Projek</th>
                        <th class="text-end">Nominal</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentIncomes as $i)
                        <tr>
                            <td>{{ $i['project'] }}</td>
                            <td class="text-end">Rp {{ number_format((float) $i['nominal'], 0, ',', '.') }}</td>
                            <td>{{ $i['tanggal'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">Belum ada transaksi pendapatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
