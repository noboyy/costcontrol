@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Perusahaan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Perusahaan</h2>
        <p>Data tenant / perusahaan</p>
    </div>
    @if($canManageAll)
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus-lg"></i> Tambah</button>
    </div>
    @endif
</div>

<div class="card">
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Pemilik</th>
                        <th>Modul</th>
                        <th>Alamat</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($list as $p)
                        <tr>
                            <td class="cell-title">{{ $p->nama_perusahaan }}</td>
                            <td>{{ $p->owner ?? '—' }}</td>
                            <td>
                                @if($p->isModuleProject())
                                    <span class="badge badge-blue">Proyek</span>
                                @elseif($p->isModuleUmkm())
                                    <span class="badge badge-yellow">UMKM</span>
                                @else
                                    <span class="badge badge-gray">Keduanya</span>
                                @endif
                            </td>
                            <td>{{ $p->alamat_lengkap ?? '—' }}</td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-outline btn-icon" onclick="openModal('edit{{ $p->id_perusahaan }}')"><i class="bi bi-pencil"></i></button>
                                    @if($canManageAll)
                                    <form action="{{ route('perusahaan.delete', $p->id_perusahaan) }}" method="POST" data-confirm="Hapus perusahaan?">
                                        @csrf
                                        <button class="btn btn-xs btn-ghost" style="color:var(--danger)"><i class="bi bi-trash3"></i></button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <div class="modal-backdrop" id="edit{{ $p->id_perusahaan }}">
                            <div class="modal">
                                <form action="{{ route('perusahaan.update', $p->id_perusahaan) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h3>Edit Perusahaan</h3>
                                        <button type="button" class="modal-close" onclick="closeModal('edit{{ $p->id_perusahaan }}')">×</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label class="form-label">Nama <span class="req">*</span></label>
                                            <input type="text" class="form-input" name="nama_perusahaan" value="{{ $p->nama_perusahaan }}" required>
                                        </div>
                                        <div class="form-group">
<label class="form-label">Pemilik</label>
                                            <input type="text" class="form-input" name="owner" value="{{ $p->owner }}">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Alamat</label>
                                            <textarea class="form-textarea" name="alamat_lengkap">{{ $p->alamat_lengkap }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Modul Aplikasi</label>
                                            <select class="form-input" name="module">
                                                <option value="all" {{ $p->isModuleAll() ? 'selected' : '' }}>Keduanya (Proyek + UMKM)</option>
                                                <option value="project" {{ $p->isModuleProject() ? 'selected' : '' }}>Hanya Proyek</option>
                                                <option value="umkm" {{ $p->isModuleUmkm() ? 'selected' : '' }}>Hanya UMKM</option>
                                            </select>
                                            <small style="color:var(--text-secondary)">UI tenant hanya menampilkan modul yang dipilih.</small>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline" onclick="closeModal('edit{{ $p->id_perusahaan }}')">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="5"><div class="empty-state">Belum ada perusahaan</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($canManageAll)
<div class="modal-backdrop" id="addModal">
    <div class="modal">
        <form action="{{ route('perusahaan.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Perusahaan</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama_perusahaan" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Pemilik</label>
                    <input type="text" class="form-input" name="owner">
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea class="form-textarea" name="alamat_lengkap"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Modul Aplikasi</label>
                    <select class="form-input" name="module">
                        <option value="all">Keduanya (Proyek + UMKM)</option>
                        <option value="project">Hanya Proyek</option>
                        <option value="umkm">Hanya UMKM</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
