@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Satuan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Satuan</h2>
        <p>Master satuan ukur untuk entri biaya & pendapatan</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus-lg"></i> Tambah Satuan</button>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" data-table-search="#dataTable" placeholder="Cari nama / simbol...">
        </div>
    </div>
    <div class="toolbar-right">
        <span class="stat-inline"><strong>{{ $units->count() }}</strong> satuan</span>
    </div>
</div>

<div class="card">
    <div class="card-body compact">
        <div class="table-wrap">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Simbol</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($units as $unit)
                        <tr data-search="{{ strtolower($unit->nama.' '.($unit->simbol ?? '')) }}">
                            <td><div class="cell-title">{{ $unit->nama }}</div></td>
                            <td><span class="badge badge-blue">{{ $unit->simbol ?? '—' }}</span></td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-outline btn-icon" title="Edit" onclick="openModal('edit{{ $unit->id_unit }}')"><i class="bi bi-pencil"></i></button>
                                    <form action="{{ route('units.delete', $unit->id_unit) }}" method="POST" data-confirm="Hapus satuan ini?">
                                        @csrf
                                        <button class="btn btn-xs btn-ghost btn-icon" style="color:var(--danger)" title="Hapus"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <i class="bi bi-rulers"></i>
                                    <p>Belum ada satuan</p>
                                    <button class="btn btn-sm btn-primary" onclick="openModal('addModal')">Tambah satuan</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($units as $unit)
    <div class="modal-backdrop" id="edit{{ $unit->id_unit }}">
        <div class="modal modal-sm">
            <form action="{{ route('units.update', $unit->id_unit) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h3>Edit Satuan</h3>
                    <button type="button" class="modal-close" onclick="closeModal('edit{{ $unit->id_unit }}')">×</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama <span class="req">*</span></label>
                        <input type="text" class="form-input" name="nama" value="{{ $unit->nama }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Simbol</label>
                        <input type="text" class="form-input" name="simbol" value="{{ $unit->simbol }}" placeholder="kg, m³, unit">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('edit{{ $unit->id_unit }}')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

<div class="modal-backdrop" id="addModal">
    <div class="modal modal-sm">
        <form action="{{ route('units.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Satuan</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama" required placeholder="Kilogram">
                </div>
                <div class="form-group">
                    <label class="form-label">Simbol</label>
                    <input type="text" class="form-input" name="simbol" placeholder="kg">
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
