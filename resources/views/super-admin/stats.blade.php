@extends('layouts.app')

@section('breadcrumb')
    <span class="current">Super Admin · Statistik Pengguna</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Dashboard Super Admin</h2>
        <p>Pantau tenant, user, dan akun investor.</p>
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
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-kanban"></i></div></div>
        <div class="kpi-label">Total Projek / UMKM</div>
        <div class="kpi-value">{{ number_format($byPlan['proyek']) }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-person-badge"></i></div></div>
        <div class="kpi-label">Akun Investor</div>
        <div class="kpi-value">{{ number_format($byPlan['investor']) }}</div>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3>Tenant / Perusahaan</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Perusahaan</th>
                    <th>Owner</th>
                    <th>Pengguna</th>
                    <th>Aktif</th>
                    <th>Projek</th>
                    <th>Investor</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perCompany as $c)
                    <tr>
                        <td>{{ $c['nama'] }}</td>
                        <td>{{ $c['owner'] }}</td>
                        <td>{{ number_format($c['pengguna']) }}</td>
                        <td><span class="badge badge-green">{{ number_format($c['aktif']) }}</span></td>
                        <td>{{ number_format($c['proyek']) }}</td>
                        <td>{{ number_format($c['investor']) }}</td>
                        <td class="text-end">
                            <div class="btn-group">
                                @if($c['id_akun'])
                                <button type="button" class="btn btn-xs btn-outline" onclick="openExtend({{ $c['id_akun'] }}, '{{ addslashes($c['nama']) }}')"><i class="bi bi-hourglass-split"></i> Perpanjang</button>
                                @endif
                                <form action="{{ route('super-admin.deleteTenant', $c['id']) }}" method="POST" data-confirm="Hapus tenant '{{ addslashes($c['nama']) }}' beserta seluruh datanya? Aksi ini tidak bisa dibatalkan." data-confirm-class="btn-danger">
                                    @csrf
                                    <button class="btn btn-xs btn-ghost" style="color:var(--danger)"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Belum ada perusahaan.</td></tr>
                @endforelse
            </tbody>
        </table>
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
                    <th>Trial Berakhir</th>
                    <th>Status</th>
                    <th>Daftar</th>
                    <th class="text-end">Aksi</th>
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
                                <span class="badge badge-green">Aktif</span>
                            @elseif($u['status'] === 'trial habis')
                                <span class="badge badge-red">Trial Habis</span>
                            @else
                                <span class="badge badge-gray">Nonaktif</span>
                            @endif
                        </td>
                        <td>{{ $u['created_at'] }}</td>
                        <td class="text-end">
                            @if($u['role'] !== 'SUPER ADMIN')
                            <div class="btn-group">
                                <button type="button" class="btn btn-xs btn-outline" onclick="openExtend({{ $u['id'] }}, '{{ addslashes($u['nama']) }}')"><i class="bi bi-hourglass-split"></i></button>
                                <form action="{{ route('super-admin.deleteUser', $u['id']) }}" method="POST" data-confirm="Hapus user '{{ addslashes($u['nama']) }}'? Aksi ini tidak bisa dibatalkan." data-confirm-class="btn-danger">
                                    @csrf
                                    <button class="btn btn-xs btn-ghost" style="color:var(--danger)"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">Belum ada user.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <h3>Akun Investor</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Perusahaan</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($investors as $i)
                    <tr>
                        <td>{{ $i['nama'] }}</td>
                        <td><code>{{ $i['username'] }}</code></td>
                        <td>{{ $i['perusahaan'] }}</td>
                        <td>
                            @if($i['is_active'] === '1')
                                <span class="badge badge-green">Aktif</span>
                            @else
                                <span class="badge badge-gray">Nonaktif</span>
                            @endif
                        </td>
                        <td>{{ $i['created_at'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">Belum ada akun investor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal-backdrop" id="extendModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <h3>Perpanjang Trial</h3>
            <button type="button" class="modal-close" onclick="closeModal('extendModal')">×</button>
        </div>
        <form method="POST" id="extendForm" action="">
            @csrf
            <div class="modal-body">
                <p class="cell-sub" style="font-size:13px;margin-bottom:14px;">Perpanjang untuk <strong id="extendTarget">—</strong></p>
                <label class="form-label">Tambahkan Berapa Hari?</label>
                <input type="number" name="days" class="form-input" value="14" min="1" max="3650" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('extendModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Perpanjang</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openExtend(id, nama) {
    const form = document.getElementById('extendForm');
    form.action = '{{ url('super-admin/trial') }}/' + id + '/extend';
    document.getElementById('extendTarget').textContent = nama;
    openModal('extendModal');
}
</script>
@endpush
@endsection