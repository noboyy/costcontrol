@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Kategori Biaya</span>
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
        <h2>Kategori Biaya</h2>
        <p>Master kategori untuk mengelompokkan tipe biaya</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('cost-types.index') }}" class="btn btn-outline"><i class="bi bi-tags"></i> Tipe Biaya</a>
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus-lg"></i> Tambah Kategori</button>
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
        <span class="stat-inline"><strong>{{ $categories->count() }}</strong> kategori</span>
    </div>
</div>

<div class="card">
    <div class="card-body compact">
        <div class="table-wrap">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th style="width:70px;">Urutan</th>
                        <th>Kategori</th>
                        <th>Kode</th>
                        <th>Warna</th>
                        <th class="text-end">Jumlah Tipe</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                        @php
                            $count = (int) ($typeCounts[$cat->kode] ?? 0);
                            $badge = $badgeMap[$cat->warna] ?? 'badge-gray';
                        @endphp
                        <tr data-search="{{ strtolower($cat->kode.' '.$cat->nama) }}">
                            <td><span class="badge badge-gray">{{ $cat->urutan }}</span></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div style="width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:var(--primary-light);color:var(--primary);">
                                        <i class="bi {{ $cat->icon ?: 'bi-folder' }}"></i>
                                    </div>
                                    <div class="cell-title">{{ $cat->nama }}</div>
                                </div>
                            </td>
                            <td><code style="font-size:12px;background:#f1f5f9;padding:2px 8px;border-radius:6px;">{{ $cat->kode }}</code></td>
                            <td><span class="badge {{ $badge }}">{{ $cat->warna }}</span></td>
                            <td class="text-end">
                                @if($count > 0)
                                    <a href="{{ route('cost-types.index') }}#{{ $cat->kode }}" class="badge badge-blue">{{ $count }} tipe</a>
                                @else
                                    <span class="cell-sub">0</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $cat->is_active ? 'badge-green' : 'badge-yellow' }}">
                                    {{ $cat->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-xs btn-outline btn-icon" title="Edit" onclick="openModal('edit{{ $cat->id_cost_category }}')"><i class="bi bi-pencil"></i></button>
                                    <form action="{{ route('cost-categories.delete', $cat->id_cost_category) }}" method="POST" data-confirm="{{ $count > 0 ? 'Masih ada tipe yang memakai kategori ini. Tetap coba hapus?' : 'Hapus kategori ini?' }}">
                                        @csrf
                                        <button class="btn btn-xs btn-ghost btn-icon" style="color:var(--danger)" title="Hapus" @disabled($count > 0)><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal-backdrop" id="edit{{ $cat->id_cost_category }}">
                            <div class="modal">
                                <form action="{{ route('cost-categories.update', $cat->id_cost_category) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h3>Edit Kategori</h3>
                                        <button type="button" class="modal-close" onclick="closeModal('edit{{ $cat->id_cost_category }}')">×</button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label class="form-label">Nama <span class="req">*</span></label>
                                            <input type="text" class="form-input" name="nama" value="{{ $cat->nama }}" required>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Kode <span class="req">*</span></label>
                                                <input type="text" class="form-input" name="kode" value="{{ $cat->kode }}" required pattern="[a-z0-9_\-]+" title="huruf kecil, angka, _ atau -">
                                                <div class="form-hint">Ubah kode = sinkron otomatis ke tipe biaya</div>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Urutan</label>
                                                <input type="number" class="form-input" name="urutan" value="{{ $cat->urutan }}" min="0">
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label class="form-label">Ikon</label>
                                                <select class="form-select" name="icon">
                                                    @foreach($iconOptions as $val => $label)
                                                        <option value="{{ $val }}" @selected(($cat->icon ?: 'bi-folder') === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Warna</label>
                                                <select class="form-select" name="warna">
                                                    @foreach($colorOptions as $val => $label)
                                                        <option value="{{ $val }}" @selected(($cat->warna ?: 'gray') === $val)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0;">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="is_active">
                                                <option value="1" @selected($cat->is_active)>Aktif</option>
                                                <option value="0" @selected(!$cat->is_active)>Nonaktif</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline" onclick="closeModal('edit{{ $cat->id_cost_category }}')">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-folder2-open"></i>
                                    <p>Belum ada kategori</p>
                                    <button class="btn btn-sm btn-primary" onclick="openModal('addModal')">Tambah kategori</button>
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
        <form action="{{ route('cost-categories.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Kategori</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama" required placeholder="Material" autofocus>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode</label>
                        <input type="text" class="form-input" name="kode" pattern="[a-z0-9_\-]+" placeholder="material (otomatis dari nama)">
                        <div class="form-hint">Opsional. Huruf kecil, angka, _ atau -</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Urutan</label>
                        <input type="number" class="form-input" name="urutan" min="0" placeholder="Otomatis">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ikon</label>
                        <select class="form-select" name="icon">
                            @foreach($iconOptions as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Warna</label>
                        <select class="form-select" name="warna">
                            @foreach($colorOptions as $val => $label)
                                <option value="{{ $val }}" @selected($val === 'gray')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <input type="hidden" name="is_active" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
