@extends('layouts.app')

@section('breadcrumb')
    <span class="current">Pengaturan</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Pengaturan Perusahaan</h2>
        <p>Identitas perusahaan untuk aplikasi finance internal.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
@endif

<div class="card" style="max-width:640px;">
    <div class="card-header">
        <h3>Identitas Perusahaan</h3>
    </div>
    <div class="card-body">
        @if($perusahaan)
        <form method="POST" action="{{ route('perusahaan.update', $perusahaan->id_perusahaan) }}">
            @csrf
            <div style="margin-bottom:14px;">
                <label class="form-label">Nama Perusahaan <span class="req">*</span></label>
                <input type="text" class="form-input" name="nama_perusahaan" value="{{ $perusahaan->nama_perusahaan }}" required>
            </div>
            <div style="margin-bottom:14px;">
                <label class="form-label">Pemilik</label>
                <input type="text" class="form-input" name="owner" value="{{ $perusahaan->owner }}">
            </div>
            <div style="margin-bottom:18px;">
                <label class="form-label">Alamat</label>
                <textarea class="form-textarea" name="alamat_lengkap" rows="3">{{ $perusahaan->alamat_lengkap }}</textarea>
            </div>
            <div style="margin-bottom:18px;">
                <label class="form-label">Saldo Awal Kas Perusahaan</label>
                <div class="input-prefix"><span>Rp</span>
                    <input type="text" class="form-input" name="opening_balance" data-money value="{{ $perusahaan->opening_balance ? number_format($perusahaan->opening_balance, 0, ',', '.') : '0' }}">
                </div>
                <div class="form-hint">Modal/uang kas awal perusahaan di luar keberangkatan. Ikut dihitung ke saldo kas global.</div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
        </form>
        @else
        <p style="color:#64748b;">Data perusahaan belum tersedia. Jalankan seeder terlebih dahulu.</p>
        @endif
    </div>
</div>
@endsection
