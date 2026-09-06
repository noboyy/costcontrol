@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Keberangkatan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Keberangkatan</h2>
        <p>Kelola trip umroh: biaya, pendapatan & margin per keberangkatan.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('addUnitModal')"><i class="bi bi-plus-lg"></i> Keberangkatan Baru</button>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="seg">
            <a href="{{ route('cost-centers.index') }}" class="{{ !$statusFilter ? 'active' : '' }}">Semua ({{ $counts['all'] }})</a>
        </div>
        <div class="seg">
            <a href="{{ route('cost-centers.index') }}" class="{{ !$statusFilter ? 'active' : '' }}">Aktif</a>
            <a href="{{ route('cost-centers.index', ['status' => 'archive']) }}" class="{{ $statusFilter === 'archive' ? 'active' : '' }}">Arsip</a>
        </div>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" data-table-search="#unitTable" placeholder="Cari nama, klien, lokasi...">
        </div>
    </div>
    <div class="toolbar-right">
        <span class="stat-inline"><strong>{{ $projects->count() }}</strong> keberangkatan</span>
    </div>
</div>

<div class="card">
    <div class="card-body compact">
        <div class="table-wrap">
            <table id="unitTable">
                <thead>
                    <tr>
                        <th>Keberangkatan</th>
                        <th>Klien / Tipe</th>
                        <th>Lokasi</th>
                        <th class="text-end">Nilai Kontrak</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        @php
                            $search = strtolower(($project->nama_project ?? '') . ' ' . ($project->client ?? '') . ' ' . ($project->lokasi ?? ''));
                            $budgetLabel = $project->project_value
                                ? 'Rp '.number_format($project->project_value, 0, ',', '.')
                                : '—';
                        @endphp
                        <tr class="clickable" data-search="{{ $search }}" onclick="location.href='{{ route('cost-centers.show', $project->id_project) }}'">
                            <td>
                                <div class="cell-title">{{ $project->nama_project }}</div>
                                @if($project->date_start)
                                    <div class="cell-sub">{{ $project->date_start->format('d M Y') }}@if($project->date_end) – {{ $project->date_end->format('d M Y') }}@endif</div>
                                @endif
                            </td>
                            <td>{{ $project->client ?? '—' }}</td>
                            <td>{{ $project->lokasi ?? '—' }}</td>
                            <td class="text-end money">{{ $budgetLabel }}</td>
                            <td>
                                <span class="badge {{ $project->isArchived() ? 'badge-gray' : 'badge-green' }}">
                                    <span class="status-dot {{ $project->isArchived() ? 'archived' : 'active' }}"></span>
                                    {{ $project->isArchived() ? 'Arsip' : 'Aktif' }}
                                </span>
                            </td>
                            <td class="text-end" onclick="event.stopPropagation()">
                                <div class="btn-group">
                                    <a href="{{ route('cost-centers.show', $project->id_project) }}" class="btn btn-xs btn-outline btn-icon" title="Detail"><i class="bi bi-eye"></i></a>
                                    <button type="button" class="btn btn-xs btn-outline btn-icon" title="Edit" onclick="openModal('editUnit{{ $project->id_project }}')"><i class="bi bi-pencil"></i></button>
                                    <form action="{{ route('cost-centers.archive', $project->id_project) }}" method="POST" data-confirm="{{ $project->isArchived() ? 'Aktifkan kembali keberangkatan ini?' : 'Arsipkan keberangkatan ini?' }}">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline btn-icon" title="{{ $project->isArchived() ? 'Aktifkan' : 'Arsip' }}">
                                            <i class="bi bi-{{ $project->isArchived() ? 'arrow-counterclockwise' : 'archive' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="bi bi-building"></i>
                                    <p>Belum ada keberangkatan</p>
                                    <button class="btn btn-primary btn-sm" onclick="openModal('addUnitModal')"><i class="bi bi-plus-lg"></i> Buat keberangkatan pertama</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($projects as $project)
    <div class="modal-backdrop" id="editUnit{{ $project->id_project }}">
        <div class="modal modal-lg">
            <form action="{{ route('cost-centers.update', $project->id_project) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h3>Edit {{ $project->mode_label }}</h3>
                    <button type="button" class="modal-close" onclick="closeModal('editUnit{{ $project->id_project }}')">×</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama <span class="req">*</span></label>
                        <input type="text" class="form-input" name="nama_project" value="{{ $project->nama_project }}" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Penyelenggara / Klien</label>
                            <input type="text" class="form-input" name="client" value="{{ $project->client }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Lokasi</label>
                            <input type="text" class="form-input" name="lokasi" value="{{ $project->lokasi }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-input" name="date_start" value="{{ $project->date_start?->format('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-input" name="date_end" value="{{ $project->date_end?->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nilai Kontrak</label>
                        <div class="input-prefix"><span>Rp</span>
                            <input type="text" class="form-input" name="project_value" data-money value="{{ $project->project_value ? number_format($project->project_value, 0, ',', '.') : '' }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Saldo Awal Kas</label>
                        <div class="input-prefix"><span>Rp</span>
                            <input type="text" class="form-input" name="opening_balance" data-money value="{{ $project->opening_balance ? number_format($project->opening_balance, 0, ',', '.') : '' }}" placeholder="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editUnit{{ $project->id_project }}')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

{{-- Add Unit --}}
<div class="modal-backdrop" id="addUnitModal">
    <div class="modal modal-lg">
        <form action="{{ route('cost-centers.store') }}" method="POST" id="addUnitForm">
            @csrf
            <div class="modal-header">
                <h3>Keberangkatan Baru</h3>
                <button type="button" class="modal-close" onclick="closeModal('addUnitModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nama Keberangkatan <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama_project" required autofocus placeholder="Contoh: Umroh Reguler Februari 2026">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Penyelenggara / Klien</label>
                        <input type="text" class="form-input" name="client" placeholder="Nama grup / penyelenggara">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi</label>
                        <input type="text" class="form-input" name="lokasi" placeholder="Kota / hotel base">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal Berangkat</label>
                        <input type="date" class="form-input" name="date_start" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Pulang</label>
                        <input type="date" class="form-input" name="date_end">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Nilai Paket / Kontrak</label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input" name="project_value" data-money placeholder="0">
                    </div>
                    <div class="form-hint">Opsional — total nilai paket seluruh jemaah</div>
                </div>

                <div class="form-group">
                    <label class="form-label">Saldo Awal Kas</label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input" name="opening_balance" data-money placeholder="0">
                    </div>
                    <div class="form-hint">Opsional — saldo kas awal trip saat dibuat</div>
                </div>

                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer;">
                    <input type="checkbox" name="generate_investor" value="1">
                    Buat akun investor otomatis (kredensial tampil sekali setelah dibuat)
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUnitModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Buat Keberangkatan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
if (location.hash === '#new') {
    openModal('addUnitModal');
    history.replaceState(null, '', location.pathname + location.search);
}
</script>
@endpush
