@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Tipe Pendapatan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Tipe Pendapatan</h2>
        <p>Master tipe pendapatan · {{ $types->count() }} tipe</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('income-categories.index') }}" class="btn btn-outline"><i class="bi bi-folder2"></i> Kategori</a>
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus-lg"></i> Tambah Tipe</button>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" data-table-search="#dataTable" placeholder="Cari kode / nama...">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body compact">
        <div class="table-wrap">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $type)
                        <tr data-search="{{ strtolower($type->kode.' '.$type->nama.' '.($type->kategori ?? '')) }}">
                            <td><span class="badge badge-gray">{{ $type->kode }}</span></td>
                            <td class="cell-title">{{ $type->nama }}</td>
                            <td>{{ $categoryLabels[$type->kategori] ?? ($type->kategori ?: '—') }}</td>
                            <td>{{ $type->default_unit ?? '—' }}</td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-outline btn-icon" onclick="openModal('edit{{ $type->id_income_type }}')"><i class="bi bi-pencil"></i></button>
                                    <form action="{{ route('income-types.delete', $type->id_income_type) }}" method="POST" data-confirm="Hapus tipe?">
                                        @csrf
                                        <button class="btn btn-xs btn-ghost" style="color:var(--danger)"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <div class="modal-backdrop" id="edit{{ $type->id_income_type }}">
                            <div class="modal">
                                <form action="{{ route('income-types.update', $type->id_income_type) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h3>Edit Tipe</h3>
                                        <button type="button" class="modal-close" onclick="closeModal('edit{{ $type->id_income_type }}')">×</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Kode *</label>
                                                <input type="text" class="form-input" name="kode" value="{{ $type->kode }}" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Nama *</label>
                                                <input type="text" class="form-input" name="nama" value="{{ $type->nama }}" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Kategori</label>
                                                <select class="form-select ts-select" name="kategori">
                                                    @foreach($categories as $c)
                                                        <option value="{{ $c->kode }}" @selected($type->kategori === $c->kode)>{{ $c->nama }}</option>
                                                    @endforeach
                                                    @if($type->kategori && !isset($categoryLabels[$type->kategori]))
                                                        <option value="{{ $type->kategori }}" selected>{{ $type->kategori }}</option>
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Satuan</label>
                                                <select class="form-select ts-select" name="default_unit">
                                                    <option value="">—</option>
                                                    @foreach($units as $u)
                                                        <option value="{{ $u->nama }}" @selected($type->default_unit === $u->nama)>{{ $u->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline" onclick="closeModal('edit{{ $type->id_income_type }}')">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><p>Belum ada tipe</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="addModal">
    <div class="modal">
        <form action="{{ route('income-types.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Tipe Pendapatan</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode *</label>
                        <input type="text" class="form-input" name="kode" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama *</label>
                        <input type="text" class="form-input" name="nama" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select class="form-select ts-select" name="kategori">
                            @forelse($categories as $c)
                                <option value="{{ $c->kode }}">{{ $c->nama }}</option>
                            @empty
                                <option value="sales">Sales</option>
                                <option value="other">Lainnya</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Satuan</label>
                        <select class="form-select ts-select" name="default_unit">
                            <option value="">—</option>
                            @foreach($units as $u)
                                <option value="{{ $u->nama }}">{{ $u->nama }}</option>
                            @endforeach
                        </select>
                    </div>
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
