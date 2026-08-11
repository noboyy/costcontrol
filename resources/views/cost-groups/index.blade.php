@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Kelompok Biaya</span>
@endsection

@section('content')
@php
    $badgeMap = [
        'blue' => 'badge-blue',
        'green' => 'badge-green',
        'yellow' => 'badge-yellow',
        'red' => 'badge-red',
        'gray' => 'badge-gray',
    ];
@endphp

<div class="page-header">
    <div>
        <h2>Kelompok Biaya</h2>
        <p>Kelompok utama pengeluaran (mis. PO, LO, OC) untuk progres per kelompok</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('cost-categories.index') }}" class="btn btn-outline"><i class="bi bi-folder2"></i> Kategori Biaya</a>
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus-lg"></i> Tambah Kelompok</button>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" data-table-search="#dataTable" placeholder="Cari kode / nama...">
        </div>
    </div>
    <div class="toolbar-right">
        <span class="stat-inline"><strong>{{ $groups->count() }}</strong> kelompok</span>
    </div>
</div>

<div class="card">
    <div class="card-body compact">
        <div class="table-wrap">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th style="width:70px;">Urutan</th>
                        <th>Kelompok</th>
                        <th>Kode</th>
                        <th>Warna</th>
                        <th class="text-end">Jumlah Kategori</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                        @php
                            $count = (int) ($categoryCounts[$group->kode] ?? 0);
                            $badge = $badgeMap[$group->warna] ?? 'badge-gray';
                        @endphp
                        <tr data-search="{{ strtolower($group->kode.' '.$group->nama) }}">
                            <td><span class="badge badge-gray">{{ $group->urutan }}</span></td>
                            <td><div class="cell-title">{{ $group->nama }}</div></td>
                            <td><code style="font-size:12px;background:#f1f5f9;padding:2px 8px;border-radius:6px;">{{ $group->kode }}</code></td>
                            <td><span class="badge {{ $badge }}">{{ $group->warna }}</span></td>
                            <td class="text-end">
                                @if($count > 0)
                                    <a href="{{ route('cost-categories.index') }}" class="badge badge-blue">{{ $count }} kategori</a>
                                @else
                                    <span class="cell-sub">0</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-outline btn-icon" title="Edit" onclick="openModal('edit{{ $group->id_cost_group }}')"><i class="bi bi-pencil"></i></button>
                                    <form action="{{ route('cost-groups.delete', $group->id_cost_group) }}" method="POST" data-confirm="{{ $count > 0 ? 'Masih ada kategori yang memakai kelompok ini. Tetap coba hapus?' : 'Hapus kelompok ini?' }}">
                                        @csrf
                                        <button class="btn btn-xs btn-ghost btn-icon" style="color:var(--danger)" title="Hapus" @disabled($count > 0)><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal-backdrop" id="edit{{ $group->id_cost_group }}">
                            <div class="modal">
                                <form action="{{ route('cost-groups.update', $group->id_cost_group) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h3>Edit Kelompok</h3>
                                        <button type="button" class="modal-close" onclick="closeModal('edit{{ $group->id_cost_group }}')">×</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label class="form-label">Nama <span class="req">*</span></label>
                                            <input type="text" class="form-input" name="nama" value="{{ $group->nama }}" required>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Kode <span class="req">*</span></label>
                                                <input type="text" class="form-input" name="kode" value="{{ $group->kode }}" required pattern="[a-z0-9_\-]+" title="huruf kecil, angka, _ atau -">
                                                <div class="form-hint">Ubah kode = sinkron otomatis ke kategori biaya</div>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Urutan</label>
                                                <input type="number" class="form-input" name="urutan" value="{{ $group->urutan }}" min="0">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Warna</label>
                                                <select class="form-select" name="warna">
                                                    @foreach($colorOptions as $val => $label)
                                                        <option value="{{ $val }}" @selected(($group->warna ?: 'gray') === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="is_active">
                                                    <option value="1" @selected($group->is_active)>Aktif</option>
                                                    <option value="0" @selected(!$group->is_active)>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline" onclick="closeModal('edit{{ $group->id_cost_group }}')">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-diagram-3"></i>
                                    <p>Belum ada kelompok</p>
                                    <button class="btn btn-sm btn-primary" onclick="openModal('addModal')">Tambah kelompok</button>
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
        <form action="{{ route('cost-groups.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Kelompok</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama" required placeholder="PO — Pembelian" autofocus>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode</label>
                        <input type="text" class="form-input" name="kode" pattern="[a-z0-9_\-]+" placeholder="po (otomatis dari nama)">
                        <div class="form-hint">Opsional. Huruf kecil, angka, _ atau -</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Urutan</label>
                        <input type="number" class="form-input" name="urutan" min="0" placeholder="Otomatis">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Warna</label>
                    <select class="form-select" name="warna">
                        @foreach($colorOptions as $val => $label)
                            <option value="{{ $val }}" @selected($val === 'gray')>{{ $label }}</option>
                        @endforeach
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
@endsection