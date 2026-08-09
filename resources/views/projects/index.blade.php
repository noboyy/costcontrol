@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Unit Bisnis</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Unit Bisnis</h2>
        <p>Cost center: Proyek konstruksi & outlet UMKM</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('addUnitModal')"><i class="bi bi-plus-lg"></i> Unit Baru</button>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="seg">
            <a href="{{ route('cost-centers.index', array_filter(['status' => $statusFilter])) }}" class="{{ !$modeFilter ? 'active' : '' }}">Semua ({{ $counts['all'] }})</a>
            @if($module !== 'umkm')
            <a href="{{ route('cost-centers.index', array_filter(['status' => $statusFilter, 'mode' => 'project'])) }}" class="{{ $modeFilter === 'project' ? 'active' : '' }}">Proyek ({{ $counts['project'] }})</a>
            @endif
            @if($module !== 'project')
            <a href="{{ route('cost-centers.index', array_filter(['status' => $statusFilter, 'mode' => 'umkm'])) }}" class="{{ $modeFilter === 'umkm' ? 'active' : '' }}">UMKM ({{ $counts['umkm'] }})</a>
            @endif
        </div>
        <div class="seg">
            <a href="{{ route('cost-centers.index', array_filter(['mode' => $modeFilter])) }}" class="{{ !$statusFilter ? 'active' : '' }}">Aktif</a>
            <a href="{{ route('cost-centers.index', array_filter(['mode' => $modeFilter, 'status' => 'archive'])) }}" class="{{ $statusFilter === 'archive' ? 'active' : '' }}">Arsip</a>
        </div>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="search" data-table-search="#unitTable" placeholder="Cari nama, klien, lokasi...">
        </div>
    </div>
    <div class="toolbar-right">
        <span class="stat-inline"><strong>{{ $projects->count() }}</strong> unit</span>
    </div>
</div>

<div class="card">
    <div class="card-body compact">
        <div class="table-wrap">
            <table id="unitTable">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Mode</th>
                        <th>Klien / Tipe</th>
                        <th>Lokasi</th>
                        <th class="text-end">Budget / Pagu</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                        @php
                            $search = strtolower(($project->nama_project ?? '') . ' ' . ($project->client ?? '') . ' ' . ($project->lokasi ?? '') . ' ' . ($project->mode ?? ''));
                            $budgetLabel = '—';
                            if ($project->isUmkm()) {
                                if ($project->budget_period === 'daily' && $project->daily_budget) {
                                    $budgetLabel = 'Rp '.number_format($project->daily_budget, 0, ',', '.').'/hari';
                                } elseif ($project->monthly_budget) {
                                    $budgetLabel = 'Rp '.number_format($project->monthly_budget, 0, ',', '.').'/bln';
                                } elseif ($project->daily_budget) {
                                    $budgetLabel = 'Rp '.number_format($project->daily_budget, 0, ',', '.').'/hari';
                                }
                            } else {
                                $budgetLabel = $project->project_value
                                    ? 'Rp '.number_format($project->project_value, 0, ',', '.')
                                    : '—';
                            }
                        @endphp
                        <tr class="clickable" data-search="{{ $search }}" onclick="location.href='{{ route('cost-centers.show', $project->id_project) }}'">
                            <td>
                                <div class="cell-title">{{ $project->nama_project }}</div>
                                @if($project->date_start)
                                    <div class="cell-sub">{{ $project->date_start->format('d M Y') }}@if($project->date_end) – {{ $project->date_end->format('d M Y') }}@endif</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $project->isUmkm() ? 'badge-yellow' : 'badge-blue' }}">
                                    <i class="bi bi-{{ $project->isUmkm() ? 'shop' : 'building' }}"></i>
                                    {{ $project->mode_label }}
                                </span>
                            </td>
                            <td>{{ $project->client ?? ($project->business_type ?? '—') }}</td>
                            <td>{{ $project->lokasi ?? '—' }}</td>
                            <td class="text-end money" style="font-size:12.5px;">{{ $budgetLabel }}</td>
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
                                    <form action="{{ route('cost-centers.archive', $project->id_project) }}" method="POST" data-confirm="{{ $project->isArchived() ? 'Aktifkan kembali unit ini?' : 'Arsipkan unit ini?' }}">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline btn-icon" title="{{ $project->isArchived() ? 'Aktifkan' : 'Arsip' }}">
                                            <i class="bi bi-{{ $project->isArchived() ? 'arrow-counterclockwise' : 'archive' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

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
                                                <label class="form-label">{{ $project->isUmkm() ? 'Jenis Usaha / Brand' : 'Klien' }}</label>
                                                <input type="text" class="form-input" name="client" value="{{ $project->client }}">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Lokasi</label>
                                                <input type="text" class="form-input" name="lokasi" value="{{ $project->lokasi }}">
                                            </div>
                                        </div>
                                        @if($project->isUmkm())
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label class="form-label">Periode Pagu</label>
                                                    <select class="form-select" name="budget_period">
                                                        <option value="daily" @selected($project->budget_period === 'daily')>Harian</option>
                                                        <option value="monthly" @selected($project->budget_period === 'monthly')>Bulanan</option>
                                                        <option value="total" @selected($project->budget_period === 'total')>Total</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Tipe Bisnis</label>
                                                    <input type="text" class="form-input" name="business_type" value="{{ $project->business_type }}" placeholder="Resto, retail, jasa...">
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label class="form-label">Pagu Harian</label>
                                                    <div class="input-prefix"><span>Rp</span>
                                                        <input type="text" class="form-input" name="daily_budget" data-money value="{{ $project->daily_budget ? number_format($project->daily_budget, 0, ',', '.') : '' }}">
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="form-label">Pagu Bulanan</label>
                                                    <div class="input-prefix"><span>Rp</span>
                                                        <input type="text" class="form-input" name="monthly_budget" data-money value="{{ $project->monthly_budget ? number_format($project->monthly_budget, 0, ',', '.') : '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        @else
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
                                            <input type="hidden" name="budget_period" value="total">
                                        @endif
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline" onclick="closeModal('editUnit{{ $project->id_project }}')">Batal</button>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-building"></i>
                                    <p>Belum ada unit bisnis</p>
                                    <button class="btn btn-primary btn-sm" onclick="openModal('addUnitModal')"><i class="bi bi-plus-lg"></i> Buat unit pertama</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add Unit --}}
<div class="modal-backdrop" id="addUnitModal">
    <div class="modal modal-lg">
        <form action="{{ route('cost-centers.store') }}" method="POST" id="addUnitForm">
            @csrf
            <div class="modal-header">
                <h3>Unit Bisnis Baru</h3>
                <button type="button" class="modal-close" onclick="closeModal('addUnitModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Mode Bisnis <span class="req">*</span></label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        @if($module !== 'umkm')
                        <label class="mode-card" style="border:1px solid var(--border-strong);border-radius:12px;padding:14px;cursor:pointer;display:block;">
                            <input type="radio" name="mode" value="project" {{ $module === 'project' ? 'checked' : '' }} onchange="toggleModeFields()" style="margin-right:8px;">
                            <strong><i class="bi bi-building"></i> Proyek</strong>
                            <div class="cell-sub" style="margin-top:4px;">RAB, kontrak, timeline konstruksi</div>
                        </label>
                        @endif
                        @if($module !== 'project')
                        <label class="mode-card" style="border:1px solid var(--border-strong);border-radius:12px;padding:14px;cursor:pointer;display:block;">
                            <input type="radio" name="mode" value="umkm" {{ $module === 'umkm' ? 'checked' : '' }} onchange="toggleModeFields()" style="margin-right:8px;">
                            <strong><i class="bi bi-shop"></i> UMKM</strong>
                            <div class="cell-sub" style="margin-top:4px;">Outlet, pagu harian/bulanan, kontrol ops</div>
                        </label>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><span id="lblName">Nama Proyek</span> <span class="req">*</span></label>
                    <input type="text" class="form-input" name="nama_project" required autofocus placeholder="Contoh: Proyek Gudang A / Warung Makan Sederhana">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" id="lblClient">Klien</label>
                        <input type="text" class="form-input" name="client" id="inputClient" placeholder="Nama klien">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi</label>
                        <input type="text" class="form-input" name="lokasi" placeholder="Kota / alamat">
                    </div>
                </div>

                {{-- Project fields --}}
                <div id="fieldsProject">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-input" name="date_start" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-input" name="date_end">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nilai Kontrak</label>
                        <div class="input-prefix"><span>Rp</span>
                            <input type="text" class="form-input" name="project_value" data-money placeholder="0">
                        </div>
                        <div class="form-hint">Opsional — untuk progress budget RAB</div>
                    </div>
                    <input type="hidden" name="budget_period" id="budgetPeriodProject" value="total">
                </div>

                {{-- UMKM fields --}}
                <div id="fieldsUmkm" style="display:none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tipe Bisnis</label>
                            <input type="text" class="form-input" name="business_type" placeholder="Resto, retail, jasa, F&B...">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Periode Pagu</label>
                            <select class="form-select" name="budget_period" id="budgetPeriodUmkm">
                                <option value="daily" selected>Harian</option>
                                <option value="monthly">Bulanan</option>
                                <option value="total">Total</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Pagu Biaya Harian</label>
                            <div class="input-prefix"><span>Rp</span>
                                <input type="text" class="form-input" name="daily_budget" data-money placeholder="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pagu Biaya Bulanan</label>
                            <div class="input-prefix"><span>Rp</span>
                                <input type="text" class="form-input" name="monthly_budget" data-money placeholder="0">
                            </div>
                        </div>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" name="seed_template" value="1" checked>
                        Seed master kategori UMKM (bahan baku, ops harian, biaya tetap, SDM)
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary);cursor:pointer;">
                        <input type="checkbox" name="generate_investor" value="1">
                        Buat akun investor otomatis (kredensial tampil sekali setelah dibuat)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUnitModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Buat Unit</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleModeFields() {
    const mode = document.querySelector('input[name="mode"]:checked')?.value || 'project';
    const isUmkm = mode === 'umkm';
    document.getElementById('fieldsProject').style.display = isUmkm ? 'none' : '';
    document.getElementById('fieldsUmkm').style.display = isUmkm ? '' : 'none';
    document.getElementById('lblName').textContent = isUmkm ? 'Nama Outlet / Unit' : 'Nama Proyek';
    document.getElementById('lblClient').textContent = isUmkm ? 'Brand / Pemilik' : 'Klien';
    document.getElementById('inputClient').placeholder = isUmkm ? 'Opsional' : 'Nama klien';
    // disable unused budget_period to avoid double submit conflict
    document.getElementById('budgetPeriodProject').disabled = isUmkm;
    document.getElementById('budgetPeriodUmkm').disabled = !isUmkm;
}
toggleModeFields();

if (location.hash === '#new') {
    openModal('addUnitModal');
    history.replaceState(null, '', location.pathname + location.search);
}
if (location.hash === '#umkm') {
    const r = document.querySelector('input[name="mode"][value="umkm"]');
    if (r) { r.checked = true; toggleModeFields(); }
    openModal('addUnitModal');
    history.replaceState(null, '', location.pathname + location.search);
}
</script>
@endpush
