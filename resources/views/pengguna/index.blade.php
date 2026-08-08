@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Pengguna</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Pengguna</h2>
        <p>Kelola akun admin sistem</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus-lg"></i> Tambah Pengguna</button>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" data-table-search="#dataTable" placeholder="Cari nama, username, jabatan...">
        </div>
    </div>
    <div class="toolbar-right">
        <span class="stat-inline"><strong>{{ $pengguna->count() }}</strong> pengguna</span>
    </div>
</div>

<div class="card">
    <div class="card-body compact">
        <div class="table-wrap">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Telepon</th>
                        <th>Jabatan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pengguna as $user)
                        <tr data-search="{{ strtolower(($user->nama_lengkap ?? '').' '.($user->akun?->username ?? '').' '.($user->jabatan ?? '')) }}">
                            <td>
                                <div class="cell-title">{{ $user->nama_lengkap }}</div>
                                @if($user->perusahaan)
                                    <div class="cell-sub">{{ $user->perusahaan->nama_perusahaan }}</div>
                                @endif
                            </td>
                            <td>{{ $user->akun?->username ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $user->akun?->role === 'SUPER ADMIN' ? 'badge-red' : 'badge-blue' }}">
                                    {{ $user->akun?->role ?? '—' }}
                                </span>
                            </td>
                            <td>{{ $user->no_hp ?? '—' }}</td>
                            <td>{{ $user->jabatan ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $user->akun?->is_active === '1' ? 'badge-green' : 'badge-yellow' }}">
                                    {{ $user->akun?->is_active === '1' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($user->akun?->role !== 'SUPER ADMIN')
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-xs btn-outline btn-icon" title="Edit" onclick="openModal('edit{{ $user->id_pengguna }}')"><i class="bi bi-pencil"></i></button>
                                        <form action="{{ route('pengguna.delete', $user->id_pengguna) }}" method="POST" data-confirm="Hapus pengguna ini?">
                                            @csrf
                                            <button class="btn btn-xs btn-ghost btn-icon" style="color:var(--danger)" title="Hapus"><i class="bi bi-trash3"></i></button>
                                        </form>
                                    </div>
                                @else
                                    <span class="cell-sub">—</span>
                                @endif
                            </td>
                        </tr>
                        @if($user->akun?->role !== 'SUPER ADMIN')
                        <div class="modal-backdrop" id="edit{{ $user->id_pengguna }}">
                            <div class="modal">
                                <form action="{{ route('pengguna.update', $user->id_pengguna) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h3>Edit Pengguna</h3>
                                        <button type="button" class="modal-close" onclick="closeModal('edit{{ $user->id_pengguna }}')">×</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                                            <input type="text" class="form-input" name="nama_lengkap" value="{{ $user->nama_lengkap }}" required>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Telepon</label>
                                                <input type="text" class="form-input" name="no_hp" value="{{ $user->no_hp }}">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Jabatan</label>
                                                <input type="text" class="form-input" name="jabatan" value="{{ $user->jabatan }}">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Password Baru</label>
                                                <input type="password" class="form-input" name="password" placeholder="Kosongkan jika tidak diubah">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="is_active">
                                                    <option value="1" @selected($user->akun?->is_active === '1')>Aktif</option>
                                                    <option value="0" @selected($user->akun?->is_active === '0')>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline" onclick="closeModal('edit{{ $user->id_pengguna }}')">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <p>Belum ada pengguna</p>
                                    <button class="btn btn-sm btn-primary" onclick="openModal('addModal')">Tambah pengguna</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="addModal">
    <div class="modal">
        <form action="{{ route('pengguna.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Pengguna</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama_lengkap" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username <span class="req">*</span></label>
                        <input type="text" class="form-input" name="username" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span class="req">*</span></label>
                        <input type="password" class="form-input" name="password" required minlength="6">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password <span class="req">*</span></label>
                    <input type="password" class="form-input" name="password_confirmation" required minlength="6">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="text" class="form-input" name="no_hp">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jabatan</label>
                        <input type="text" class="form-input" name="jabatan" placeholder="Admin Project">
                    </div>
                </div>
                <div class="alert alert-info" style="margin:0;">
                    <i class="bi bi-info-circle"></i>
                    <span>Akun baru dibuat dengan role <strong>ADMIN</strong>.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
