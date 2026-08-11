@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Tipe Biaya</span>
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
        <h2>Tipe Biaya</h2>
        <p>Dikelompokkan per kategori · {{ $types->count() }} tipe</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('cost-categories.index') }}" class="btn btn-outline"><i class="bi bi-folder2"></i> Kelola Kategori</a>
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus-lg"></i> Tambah Tipe</button>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="seg" id="categorySeg">
            <button type="button" class="active" data-cat="all" onclick="filterCategory('all', this)">Semua</button>
            @foreach($typesByCategory as $cat => $items)
                @php $label = $categoryLabels[$cat] ?? ucfirst($cat); @endphp
                <button type="button" data-cat="{{ $cat }}" onclick="filterCategory('{{ $cat }}', this)">
                    {{ $label }} ({{ $items->count() }})
                </button>
            @endforeach
        </div>
    </div>
    <div class="toolbar-right">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" id="typeSearch" placeholder="Cari kode / nama...">
        </div>
    </div>
</div>

@forelse($typesByCategory as $cat => $items)
    @php
        $label = $categoryLabels[$cat] ?? ucfirst(str_replace('_', ' ', $cat));
        $meta = $categoryMeta[$cat] ?? ['icon' => 'bi-folder', 'color' => 'gray'];
        $badge = $badgeMap[$meta['color'] ?? 'gray'] ?? 'badge-gray';
    @endphp
    <div class="category-block" data-category="{{ $cat }}" id="cat-{{ $cat }}" style="margin-bottom:18px;">
        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="bi {{ $meta['icon'] ?? 'bi-folder' }}"></i>
                    {{ $label }}
                    <span class="badge {{ $badge }}" style="margin-left:4px;">{{ $items->count() }}</span>
                </h3>
            </div>
            <div class="card-body compact">
                @if($items->isEmpty())
                    <div class="empty-state" style="padding:28px;">
                        <i class="bi bi-inbox"></i>
                        <p>Belum ada tipe di kategori ini</p>
                        <button class="btn btn-sm btn-outline" onclick="openAddFor('{{ $cat }}')"><i class="bi bi-plus"></i> Tambah tipe</button>
                    </div>
                @else
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Satuan Default</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $type)
                                <tr data-search="{{ strtolower($type->kode.' '.$type->nama.' '.($type->kategori ?? '')) }}">
                                    <td><span class="badge badge-gray">{{ $type->kode }}</span></td>
                                    <td><div class="cell-title">{{ $type->nama }}</div></td>
                                    <td>{{ $type->default_unit ?? '—' }}</td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-xs btn-outline btn-icon" title="Edit" onclick="openModal('edit{{ $type->id_cost_type }}')"><i class="bi bi-pencil"></i></button>
                                            <form action="{{ route('cost-types.delete', $type->id_cost_type) }}" method="POST" data-confirm="Hapus tipe biaya ini?">
                                                @csrf
                                                <button class="btn btn-xs btn-ghost btn-icon" style="color:var(--danger)" title="Hapus"><i class="bi bi-trash3"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    @foreach($items as $type)
    <div class="modal-backdrop" id="edit{{ $type->id_cost_type }}">
        <div class="modal">
            <form action="{{ route('cost-types.update', $type->id_cost_type) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h3>Edit Tipe Biaya</h3>
                    <button type="button" class="modal-close" onclick="closeModal('edit{{ $type->id_cost_type }}')">×</button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kode <span class="req">*</span></label>
                            <input type="text" class="form-input" name="kode" value="{{ $type->kode }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nama <span class="req">*</span></label>
                            <input type="text" class="form-input" name="nama" value="{{ $type->nama }}" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kategori <span class="req">*</span></label>
                            <select class="form-select" name="kategori" required>
                                @foreach($categoryLabels as $key => $catLabel)
                                    <option value="{{ $key }}" @selected(strtolower($type->kategori ?? '') === $key)>{{ $catLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Satuan Default</label>
                            <select class="form-select" name="default_unit">
                                <option value="">—</option>
                                @foreach($units as $u)
                                    <option value="{{ $u->nama }}" @selected($type->default_unit === $u->nama)>{{ $u->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('edit{{ $type->id_cost_type }}')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
@empty
    <div class="card">
        <div class="empty-state">
            <i class="bi bi-tags"></i>
            <p>Belum ada tipe biaya</p>
            <div class="btn-group">
                <a href="{{ route('cost-categories.index') }}" class="btn btn-sm btn-outline">Buat kategori dulu</a>
                <button class="btn btn-sm btn-primary" onclick="openModal('addModal')">Tambah tipe</button>
            </div>
        </div>
    </div>
@endforelse

<div id="noSearchResult" class="card" style="display:none;">
    <div class="empty-state">
        <i class="bi bi-search"></i>
        <p>Tidak ada tipe yang cocok</p>
    </div>
</div>

<div class="modal-backdrop" id="addModal">
    <div class="modal">
        <form action="{{ route('cost-types.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Tambah Tipe Biaya</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode <span class="req">*</span></label>
                        <input type="text" class="form-input" name="kode" required placeholder="MAT">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama <span class="req">*</span></label>
                        <input type="text" class="form-input" name="nama" required placeholder="Material Bangunan">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kategori <span class="req">*</span></label>
                        <select class="form-select" name="kategori" id="addKategori" required>
                            @forelse($categories as $c)
                                <option value="{{ $c->kode }}">{{ $c->nama }}</option>
                            @empty
                                @foreach($categoryLabels as $key => $catLabel)
                                    <option value="{{ $key }}">{{ $catLabel }}</option>
                                @endforeach
                            @endforelse
                        </select>
                        @if($categories->isEmpty())
                            <div class="form-hint"><a href="{{ route('cost-categories.index') }}">Kelola kategori</a> untuk opsi lebih rapi</div>
                        @endif
                    </div>
                    <div class="form-group">
                        <label class="form-label">Satuan Default</label>
                        <select class="form-select" name="default_unit">
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

@push('scripts')
<script>
let activeCat = 'all';

function filterCategory(cat, el) {
    activeCat = cat;
    document.querySelectorAll('#categorySeg button').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    applyFilter();
}

function applyFilter() {
    const q = (document.getElementById('typeSearch')?.value || '').toLowerCase().trim();
    let visibleBlocks = 0;

    document.querySelectorAll('.category-block').forEach(block => {
        const cat = block.dataset.category;
        const catOk = activeCat === 'all' || cat === activeCat;
        const rows = block.querySelectorAll('tbody tr[data-search]');
        let rowVisible = 0;

        if (rows.length === 0) {
            // empty category block
            block.style.display = catOk && !q ? '' : 'none';
            if (catOk && !q) visibleBlocks++;
            return;
        }

        rows.forEach(row => {
            const searchOk = !q || row.dataset.search.includes(q);
            const show = catOk && searchOk;
            row.style.display = show ? '' : 'none';
            if (show) rowVisible++;
        });

        block.style.display = catOk && rowVisible > 0 ? '' : 'none';
        if (catOk && rowVisible > 0) visibleBlocks++;
    });

    const empty = document.getElementById('noSearchResult');
    if (empty) empty.style.display = visibleBlocks === 0 ? '' : 'none';
}

function openAddFor(cat) {
    const sel = document.getElementById('addKategori');
    if (sel) sel.value = cat;
    openModal('addModal');
}

document.getElementById('typeSearch')?.addEventListener('input', applyFilter);

// Hash deep-link e.g. /cost-types#material
if (location.hash) {
    const key = location.hash.replace('#', '');
    const btn = document.querySelector(`#categorySeg button[data-cat="${key}"]`);
    if (btn) filterCategory(key, btn);
}
</script>
@endpush
