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

            <div style="margin-bottom:14px;padding-top:12px;border-top:1px solid var(--border);">
                <label class="form-label">Mode Perkiraan Pajak</label>
                <select class="form-select" name="mode_pajak">
                    <option value="final_omzet" @selected($perusahaan->mode_pajak === 'final_omzet')>PPh Final 0,5% dari Omzet (PP 55/2022 — CV/UMKM &lt; 4,8 M/thn)</option>
                    <option value="badan_laba" @selected($perusahaan->mode_pajak === 'badan_laba')>PPh Badan dari Laba (non-final)</option>
                </select>
                <div class="form-hint">Omzet = seluruh pendapatan (income). Mode laba hanya estimasi akuntansi — beda dari laba fiskal.</div>
            </div>
            <div class="form-row">
                <div style="flex:1;margin-right:8px;">
                    <label class="form-label">Tarif Final Omzet (%)</label>
                    <input type="text" class="form-input" name="tarif_pph_omzet" value="{{ rtrim(rtrim(number_format($perusahaan->tarif_pph_omzet, 2, '.', ''), '0'), '.') }}">
                </div>
                <div style="flex:1;">
                    <label class="form-label">Tarif Laba Badan (%)</label>
                    <input type="text" class="form-input" name="tarif_pph_laba" value="{{ rtrim(rtrim(number_format($perusahaan->tarif_pph_laba, 2, '.', ''), '0'), '.') }}">
                </div>
            </div>
            <div style="margin-bottom:18px;display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="pajak_aktif" value="1" id="pajakAktif" @checked($perusahaan->pajak_aktif)>
                <label for="pajakAktif" style="font-size:13px;color:var(--text-secondary);cursor:pointer;">Aktifkan perkiraan pajak di laporan</label>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
        </form>
        @else
        <p style="color:#64748b;">Data perusahaan belum tersedia. Jalankan seeder terlebih dahulu.</p>
        @endif
    </div>
</div>
@endsection
