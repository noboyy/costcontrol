@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Pengeluaran Umum</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Pengeluaran Umum</h2>
        <p>Biaya operasional perusahaan yang tidak terkait keberangkatan tertentu</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('addExpenseModal')"><i class="bi bi-plus-lg"></i> Catat Pengeluaran</button>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-wallet2"></i></div></div>
        <div class="kpi-label">Saldo Kas Perusahaan</div>
        <div class="kpi-value money {{ ($position['balance'] ?? 0) < 0 ? 'negative' : '' }}">Rp {{ number_format($position['balance'] ?? 0, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">Awal + pemasukan − pengeluaran semua keberangkatan</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><i class="bi bi-arrow-down-circle"></i></div></div>
        <div class="kpi-label">Biaya Umum Bulan Ini</div>
        <div class="kpi-value">Rp {{ number_format($summary['cost_general'] ?? 0, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">{{ \Carbon\Carbon::parse($month)->translatedFormat('F Y') }}</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><i class="bi bi-arrow-up-circle"></i></div></div>
        <div class="kpi-label">Pemasukan Bulan Ini</div>
        <div class="kpi-value">Rp {{ number_format($summary['income'] ?? 0, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">Total semua sumber</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon yellow"><i class="bi bi-receipt"></i></div></div>
        <div class="kpi-label">Biaya Keberangkatan Bulan Ini</div>
        <div class="kpi-value">Rp {{ number_format($summary['cost_project'] ?? 0, 0, ',', '.') }}</div>
        <div class="kpi-change neutral">Per-keberangkatan</div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="GET" action="{{ route('general-expenses.index') }}" class="form-row" style="align-items:end;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Bulan</label>
                <input type="month" class="form-input" name="month" value="{{ $month }}">
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="bi bi-receipt"></i> Pengeluaran Umum ({{ $monthTotal > 0 ? 'Rp '.number_format($monthTotal, 0, ',', '.') : '0' }})</h3>
    </div>
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Keterangan</th>
                        <th class="text-end">Nominal</th>
                        <th>Bukti</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                        <tr>
                            <td style="white-space:nowrap;">{{ $e->tanggal?->format('d M Y') }}</td>
                            <td>{{ $e->costType?->nama ?? '—' }}</td>
                            <td>{{ $e->keterangan ?? '—' }}</td>
                            <td class="text-end money negative">Rp {{ number_format($e->total, 0, ',', '.') }}</td>
                            <td>
                                @if($e->file_bukti)
                                    <a href="{{ route('general-expenses.bukti', $e->id_cost) }}" target="_blank" class="btn btn-xs btn-outline"><i class="bi bi-paperclip"></i> Lihat</a>
                                @else
                                    <span class="cell-sub">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form action="{{ route('general-expenses.delete', $e->id_cost) }}" method="POST" data-confirm="Hapus pengeluaran umum ini?">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline btn-icon"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"><div class="empty-state">Belum ada pengeluaran umum bulan ini</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="addExpenseModal">
    <div class="modal">
        <form action="{{ route('general-expenses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h3>Catat Pengeluaran Umum</h3>
                <button type="button" class="modal-close" onclick="closeModal('addExpenseModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Tipe Biaya <span class="req">*</span></label>
                    <select class="form-select" name="id_cost_type" required>
                        <option value="">Pilih tipe biaya</option>
                        @foreach($costTypes as $t)
                            <option value="{{ $t->id_cost_type }}">{{ $t->kategori ? ucfirst(str_replace('_', ' ', $t->kategori)).' · ' : '' }}{{ $t->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="req">*</span></label>
                    <input type="date" class="form-input" name="tanggal" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-input" name="keterangan" maxlength="255" placeholder="Contoh: Sewa kantor bulan September">
                </div>
                <div class="form-group">
                    <label class="form-label">Nominal <span class="req">*</span></label>
                    <div class="input-prefix"><span>Rp</span>
                        <input type="text" class="form-input" name="total" data-money placeholder="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Bukti (opsional)</label>
                    <input type="file" class="form-input" name="file_bukti" accept="image/*,.pdf">
                    <div class="form-hint">JPG, PNG, WEBP, atau PDF maks 5 MB</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addExpenseModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
