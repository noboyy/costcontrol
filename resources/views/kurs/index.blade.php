@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Kurs Mata Uang</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Kurs Mata Uang</h2>
        <p>Kurs rupiah utk pengeluaran vendor luar negeri (USD & SAR)</p>
    </div>
    <div class="page-actions">
        <form action="{{ route('kurs.fetch-bi') }}" method="POST" style="display:inline;">
            @csrf
            <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-download"></i> Ambil Kurs BI Sekarang</button>
        </form>
        <button class="btn btn-outline" onclick="openModal('addKursModal')"><i class="bi bi-plus-lg"></i> Isi Manual</button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="margin-bottom:16px;">{{ session('error') }}</div>
@endif

@if(!empty($latest))
<div class="kpi-grid" style="margin-bottom:16px;">
    @foreach($latest as $cur => $k)
        @if($k)
        <div class="kpi-card">
            <div class="kpi-top"><div class="kpi-icon blue"><i class="bi bi-currency-exchange"></i></div></div>
            <div class="kpi-label">{{ $cur }} / IDR</div>
            <div class="kpi-value">Rp {{ number_format($k->kurs, 2, ',', '.') }}</div>
            <div class="kpi-change neutral">per {{ $k->tanggal->format('d M Y') }} ({{ $k->sumber === 'bi' ? 'Kurs BI' : 'Manual' }})</div>
        </div>
        @endif
    @endforeach
</div>
@endif

<div class="card" style="margin-bottom:16px;">
    <div class="card-body">
        <form method="GET" action="{{ route('kurs.index') }}" class="form-row" style="align-items:end;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Bulan</label>
                <input type="month" class="form-input" name="bulan" value="{{ $month }}">
            </div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="bi bi-currency-exchange"></i> Riwayat Kurs</h3></div>
    <div class="card-body compact">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Mata Uang</th>
                        <th>Sumber</th>
                        <th class="text-end">Kurs (IDR)</th>
                        <th>Keterangan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td style="white-space:nowrap;">{{ $r->tanggal->format('d M Y') }}</td>
                            <td><span class="badge badge-blue">{{ $r->mata_uang }}</span></td>
                            <td>{{ $r->sumber === 'bi' ? 'Kurs BI' : 'Manual' }}</td>
                            <td class="text-end money">Rp {{ number_format($r->kurs, 2, ',', '.') }}</td>
                            <td>{{ $r->keterangan ?? '—' }}</td>
                            <td class="text-end">
                                <form action="{{ route('kurs.delete', $r->id) }}" method="POST" data-confirm="Hapus data kurs ini?">
                                    @csrf
                                    <button type="submit" class="btn btn-xs btn-outline btn-icon"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state">Belum ada data kurs. Ambil dari BI atau isi manual.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="addKursModal">
    <div class="modal">
        <form action="{{ route('kurs.store') }}" method="POST">
            @csrf
            <div class="modal-header">
                <h3>Isi Kurs Manual</h3>
                <button type="button" class="modal-close" onclick="closeModal('addKursModal')">×</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="req">*</span></label>
                    <input type="date" class="form-input" name="tanggal" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mata Uang <span class="req">*</span></label>
                    <select class="form-select" name="mata_uang">
                        <option value="USD">USD — Dolar AS</option>
                        <option value="SAR">SAR — Riyal Saudi</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Kurs (IDR per 1 unit) <span class="req">*</span></label>
                    <input type="text" class="form-input" name="kurs" placeholder="Contoh: 4250" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan</label>
                    <input type="text" class="form-input" name="keterangan" maxlength="255" placeholder="Contoh: kurs beli bank">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addKursModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
