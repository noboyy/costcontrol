@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Aset</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Aset</h2>
        <p>Inventaris & perawatan aset perusahaan</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus-lg"></i> Tambah Aset</button>
    </div>
</div>

@php
    $activeAssets = $assets->filter(fn($a) => !$a->isSold());
    $soldAssets = $assets->filter(fn($a) => $a->isSold());
@endphp

<div class="toolbar">
    <div class="toolbar-left">
        <div class="seg">
            <button type="button" class="active" data-filter="all" onclick="filterAssets('all', this)">Semua ({{ $assets->count() }})</button>
            <button type="button" data-filter="aktif" onclick="filterAssets('aktif', this)">Aktif ({{ $activeAssets->count() }})</button>
            <button type="button" data-filter="dijual" onclick="filterAssets('dijual', this)">Dijual ({{ $soldAssets->count() }})</button>
        </div>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" id="assetSearch" placeholder="Cari nama aset...">
        </div>
    </div>
</div>

<div class="grid-3" id="assetGrid">
    @forelse($assets as $asset)
        <div class="card asset-card" data-status="{{ $asset->isSold() ? 'dijual' : 'aktif' }}" data-search="{{ strtolower($asset->nama_asset.' '.($asset->keterangan ?? '')) }}">
            @if($asset->gambar)
                <img src="{{ route('asset.image', $asset->id_asset) }}" alt="{{ $asset->nama_asset }}" style="width:100%;height:170px;object-fit:cover;border-radius:var(--radius) var(--radius) 0 0;">
            @else
                <div style="height:170px;background:linear-gradient(145deg,#f1f5f9,#e2e8f0);display:grid;place-items:center;border-radius:var(--radius) var(--radius) 0 0;">
                    <i class="bi bi-box-seam" style="font-size:36px;color:#94a3b8;"></i>
                </div>
            @endif
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:start;gap:8px;margin-bottom:8px;">
                    <strong style="font-size:14.5px;">{{ $asset->nama_asset }}</strong>
                    <span class="badge {{ $asset->isSold() ? 'badge-red' : 'badge-green' }}">{{ $asset->status }}</span>
                </div>
                @if($asset->nilai_asset)
                    <div class="cell-sub" style="margin-bottom:4px;">Nilai: <span class="money">Rp {{ number_format($asset->nilai_asset, 0, ',', '.') }}</span></div>
                @endif
                @if($asset->keterangan)
                    <div style="font-size:12.5px;color:var(--text-secondary);margin-bottom:8px;">{{ Str::limit($asset->keterangan, 90) }}</div>
                @endif
                @if($asset->isSold())
                    <div style="background:var(--danger-light);padding:8px 10px;border-radius:var(--radius-xs);font-size:11.5px;color:var(--danger);">
                        Dijual {{ $asset->tanggal_jual?->format('d M Y') }} · Rp {{ number_format($asset->nilai_jual ?? 0, 0, ',', '.') }}
                    </div>
                @endif
                @if($asset->maintenanceRecords->count() > 0)
                    <div class="cell-sub" style="margin-top:8px;"><i class="bi bi-wrench"></i> {{ $asset->maintenanceRecords->count() }} perawatan</div>
                @endif
            </div>
            <div class="card-footer" style="display:flex;gap:6px;flex-wrap:wrap;">
                @if(!$asset->isSold())
                    <button type="button" class="btn btn-xs btn-outline" onclick="openModal('edit{{ $asset->id_asset }}')"><i class="bi bi-pencil"></i> Edit</button>
                    <button type="button" class="btn btn-xs btn-outline" onclick="openModal('maint{{ $asset->id_asset }}')"><i class="bi bi-wrench"></i> Perawatan</button>
                    <button type="button" class="btn btn-xs btn-outline" style="color:var(--success)" onclick="openModal('sell{{ $asset->id_asset }}')"><i class="bi bi-cash"></i> Jual</button>
                @endif
                <form action="{{ route('asset.delete', $asset->id_asset) }}" method="POST" data-confirm="Hapus aset ini?" style="margin-left:auto;">
                    @csrf
                    <button class="btn btn-xs btn-ghost" style="color:var(--danger)"><i class="bi bi-trash3"></i></button>
                </form>
            </div>
        </div>

        <div class="modal-backdrop" id="edit{{ $asset->id_asset }}">
            <div class="modal">
                <form action="{{ route('asset.update', $asset->id_asset) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h3>Edit Aset</h3>
                        <button type="button" class="modal-close" onclick="closeModal('edit{{ $asset->id_asset }}')">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Nama <span class="req">*</span></label>
                            <input type="text" class="form-input" name="nama_asset" value="{{ $asset->nama_asset }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nilai</label>
                            <div class="input-prefix">
                                <span>Rp</span>
                                <input type="text" class="form-input" name="nilai_asset" data-money value="{{ $asset->nilai_asset ? number_format($asset->nilai_asset, 0, ',', '.') : '' }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-textarea" name="keterangan">{{ $asset->keterangan }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gambar</label>
                            <input type="file" class="form-input" name="gambar" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeModal('edit{{ $asset->id_asset }}')">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-backdrop" id="maint{{ $asset->id_asset }}">
            <div class="modal">
                <form action="{{ route('asset.addMaintenance', $asset->id_asset) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h3>Catat Perawatan</h3>
                        <button type="button" class="modal-close" onclick="closeModal('maint{{ $asset->id_asset }}')">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-input" name="tanggal" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-textarea" name="keterangan" placeholder="Service, ganti oli, dll."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Biaya <span class="req">*</span></label>
                            <div class="input-prefix">
                                <span>Rp</span>
                                <input type="text" class="form-input" name="biaya" data-money required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeModal('maint{{ $asset->id_asset }}')">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-backdrop" id="sell{{ $asset->id_asset }}">
            <div class="modal">
                <form action="{{ route('asset.sell', $asset->id_asset) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h3>Jual Aset</h3>
                        <button type="button" class="modal-close" onclick="closeModal('sell{{ $asset->id_asset }}')">×</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info" style="margin-bottom:14px;">
                            <i class="bi bi-info-circle"></i>
                            <span>Aset <strong>{{ $asset->nama_asset }}</strong> akan ditandai terjual.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Jual</label>
                            <input type="date" class="form-input" name="tanggal_jual" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Harga Jual <span class="req">*</span></label>
                            <div class="input-prefix">
                                <span>Rp</span>
                                <input type="text" class="form-input" name="nilai_jual" data-money required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alasan</label>
                            <textarea class="form-textarea" name="alasan_jual"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeModal('sell{{ $asset->id_asset }}')">Batal</button>
                        <button type="submit" class="btn btn-success">Konfirmasi Jual</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div style="grid-column:1/-1;">
            <div class="card">
                <div class="empty-state">
                    <i class="bi bi-box-seam"></i>
                    <p>Belum ada aset</p>
                    <button class="btn btn-sm btn-primary" onclick="openModal('addModal')"><i class="bi bi-plus"></i> Tambah aset pertama</button>
                </div>
            </div>
        </div>
    @endforelse
</div>

<div class="modal-backdrop" id="addModal">
    <div class="modal">
        <form action="{{ route('asset.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h3>Tambah Aset</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama_asset" required placeholder="Excavator PC200">
                </div>
                <div class="form-group">
                    <label class="form-label">Nilai</label>
                    <div class="input-prefix">
                        <span>Rp</span>
                        <input type="text" class="form-input" name="nilai_asset" data-money placeholder="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <textarea class="form-textarea" name="keterangan" placeholder="Spesifikasi, nomor unit, dll."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Gambar</label>
                    <input type="file" class="form-input" name="gambar" accept="image/*">
                    <div class="form-hint">JPG, PNG, WEBP · maks 3MB</div>
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
let assetFilter = 'all';
function filterAssets(status, el) {
    assetFilter = status;
    document.querySelectorAll('.seg button').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    applyAssetFilter();
}
function applyAssetFilter() {
    const q = (document.getElementById('assetSearch')?.value || '').toLowerCase().trim();
    document.querySelectorAll('.asset-card').forEach(card => {
        const okStatus = assetFilter === 'all' || card.dataset.status === assetFilter;
        const okSearch = !q || card.dataset.search.includes(q);
        card.style.display = okStatus && okSearch ? '' : 'none';
    });
}
document.getElementById('assetSearch')?.addEventListener('input', applyAssetFilter);
</script>
@endpush
