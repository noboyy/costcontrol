@extends('layouts.app')

@section('breadcrumb')
    <span class="current">Super Admin · Statistik Pengguna</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Statistik Pengguna</h2>
        <p>Berapa user yang aktif menggunakan aplikasi.</p>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-people"></i></div></div>
        <div class="kpi-label">Total User</div>
        <div class="kpi-value">{{ number_format($byPlan['total']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-person-check"></i></div></div>
        <div class="kpi-label">User Aktif</div>
        <div class="kpi-value">{{ number_format($byPlan['aktif']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-hourglass-split"></i></div></div>
        <div class="kpi-label">Trial Berjalan</div>
        <div class="kpi-value">{{ number_format($byPlan['trial_berjalan']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-hourglass-bottom"></i></div></div>
        <div class="kpi-label">Trial Habis</div>
        <div class="kpi-value">{{ number_format($byPlan['trial_habis']) }}</div>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon yellow"><i class="bi bi-person-x"></i></div></div>
        <div class="kpi-label">Akun Nonaktif</div>
        <div class="kpi-value">{{ number_format($byPlan['nonaktif']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-building"></i></div></div>
        <div class="kpi-label">Total Perusahaan</div>
        <div class="kpi-value">{{ number_format($byPlan['perusahaan']) }}</div>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3>User per Perusahaan</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Perusahaan</th>
                    <th>Owner</th>
                    <th>Pengguna</th>
                    <th>Aktif</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perCompany as $c)
                    <tr>
                        <td>{{ $c['nama'] }}</td>
                        <td>{{ $c['owner'] }}</td>
                        <td>{{ number_format($c['pengguna']) }}</td>
                        <td><span class="badge badge-success">{{ number_format($c['aktif']) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">Belum ada perusahaan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3>User Terbaru</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Trial Berakhir</th>
                    <th>Status</th>
                    <th>Daftar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentUsers as $u)
                    <tr>
                        <td>{{ $u['nama'] }}</td>
                        <td>{{ $u['email'] }}</td>
                        <td>{{ $u['role'] }}</td>
                        <td>{{ $u['trial_ends_at'] ?? '-' }}</td>
                        <td>
                            @if($u['status'] === 'aktif')
                                <span class="badge badge-success">Aktif</span>
                            @elseif($u['status'] === 'trial habis')
                                <span class="badge badge-danger">Trial Habis</span>
                            @else
                                <span class="badge badge-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>{{ $u['created_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Belum ada user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection