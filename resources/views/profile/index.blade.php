@extends('layouts.app')

@section('breadcrumb')
    <a href="{{ route('beranda') }}">Dashboard</a>
    <span class="sep">/</span>
    <span class="current">Profil</span>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h2>Profil</h2>
        <p>Kelola data diri & keamanan akun</p>
    </div>
</div>

<div class="grid-2" style="grid-template-columns: 300px 1fr; align-items:start;">
    <div>
        <div class="card" style="text-align:center;">
            <div class="card-body">
                <div style="margin-bottom:14px;">
                    <img src="{{ route('profil.photo') }}?v={{ time() }}" class="avatar-lg" alt="Foto" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect fill=%22%23dbeafe%22 width=%22100%22 height=%22100%22/><text x=%2250%22 y=%2258%22 text-anchor=%22middle%22 font-size=%2240%22 fill=%22%232563eb%22>{{ strtoupper(substr($profile->nama_lengkap ?? $user->username ?? 'A', 0, 1)) }}</text></svg>'">
                </div>
                <h3 style="font-size:16px;margin-bottom:6px;">{{ $profile->nama_lengkap ?? $user->username }}</h3>
                <span class="badge badge-blue">{{ $user->role }}</span>
                @if($profile->perusahaan)
                    <div class="cell-sub" style="margin-top:10px;">{{ $profile->perusahaan->nama_perusahaan }}</div>
                @endif
                @if($profile->jabatan)
                    <div style="font-size:12.5px;color:var(--text-secondary);margin-top:2px;">{{ $profile->jabatan }}</div>
                @endif
                <div style="margin-top:16px;">
                    <input type="file" id="photoInput" accept="image/*" style="display:none">
                    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('photoInput').click()">
                        <i class="bi bi-camera"></i> Ganti Foto
                    </button>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:14px;">
            <div class="card-header"><h3>Info Akun</h3></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <div class="cell-sub" style="text-transform:uppercase;letter-spacing:0.04em;">Username</div>
                    <div style="font-weight:600;">{{ $user->username }}</div>
                </div>
                <div>
                    <div class="cell-sub" style="text-transform:uppercase;letter-spacing:0.04em;">Status</div>
                    <span class="badge {{ $user->is_active === '1' ? 'badge-green' : 'badge-yellow' }}">
                        {{ $user->is_active === '1' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div>
                    <div class="cell-sub" style="text-transform:uppercase;letter-spacing:0.04em;">Bergabung</div>
                    <div style="font-size:13px;">{{ $user->created_at?->format('d M Y') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:14px;">
            <div class="card-header"><h3><i class="bi bi-person"></i> Data Pribadi</h3></div>
            <div class="card-body">
                <form action="{{ route('profil.updateData') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                        <input type="text" class="form-input" name="nama_lengkap" value="{{ $profile->nama_lengkap ?? '' }}" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-input" name="no_hp" value="{{ $profile->no_hp ?? '' }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Jabatan</label>
                            <input type="text" class="form-input" name="jabatan" value="{{ $profile->jabatan ?? '' }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <textarea class="form-textarea" name="alamat" rows="2">{{ $profile->alamat ?? '' }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Perubahan</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3><i class="bi bi-shield-lock"></i> Ubah Kata Sandi</h3></div>
            <div class="card-body">
                <form action="{{ route('profil.updatePassword') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Kata Sandi Saat Ini <span class="req">*</span></label>
                        <input type="password" class="form-input" name="current_password" required autocomplete="current-password">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kata Sandi Baru <span class="req">*</span></label>
                            <input type="password" class="form-input" name="new_password" required minlength="6" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Konfirmasi Kata Sandi <span class="req">*</span></label>
                            <input type="password" class="form-input" name="new_password_confirmation" required minlength="6" autocomplete="new-password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-key"></i> Perbarui Kata Sandi</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('photoInput')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        showToast('Maksimal 2MB', 'error');
        return;
    }
    const fd = new FormData();
    fd.append('photo', file);
    fd.append('_token', '{{ csrf_token() }}');
    fetch('{{ route("profil.updatePhoto") }}', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) location.reload();
            else showToast(d.message || 'Gagal upload', 'error');
        })
        .catch(() => showToast('Gagal upload foto', 'error'));
});
</script>
@endpush
