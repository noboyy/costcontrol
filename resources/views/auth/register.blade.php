<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — CostControl</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8f9fb; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { width: 100%; max-width: 480px; }
        .brand { text-align: center; margin-bottom: 32px; }
        .brand-icon { width: 48px; height: 48px; background: #2563eb; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .brand-icon svg { width: 24px; height: 24px; fill: white; }
        .brand h1 { font-size: 20px; font-weight: 700; color: #111827; }
        .brand p { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .section-title { font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #f3f4f6; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-input { width: 100%; padding: 10px 12px; font-size: 14px; font-family: inherit; border: 1px solid #d1d5db; border-radius: 8px; transition: all 0.15s; outline: none; }
        .form-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-textarea { width: 100%; padding: 10px 12px; font-size: 14px; font-family: inherit; border: 1px solid #d1d5db; border-radius: 8px; resize: vertical; min-height: 60px; outline: none; }
        .form-textarea:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn { width: 100%; padding: 10px; font-size: 14px; font-weight: 500; font-family: inherit; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; transition: all 0.15s; }
        .btn:hover { background: #1d4ed8; }
        .alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 8px; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand">
            <div class="brand-icon"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg></div>
            <h1>CostControl</h1>
            <p>Create your first admin account</p>
        </div>

        <div class="card">
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="section-title">Company</div>
                <div class="form-group">
                    <label class="form-label">Company Name *</label>
                    <input type="text" class="form-input" name="nama_perusahaan" value="{{ old('nama_perusahaan') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Company Address</label>
                    <textarea class="form-textarea" name="alamat_perusahaan" rows="2">{{ old('alamat_perusahaan') }}</textarea>
                </div>

                <div class="section-title" style="margin-top:24px;">Personal Info</div>
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-input" name="nama_lengkap" value="{{ old('nama_lengkap') }}" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-input" name="no_hp" value="{{ old('no_hp') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-input" name="jabatan" value="{{ old('jabatan') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea class="form-textarea" name="alamat" rows="2">{{ old('alamat') }}</textarea>
                </div>

                <div class="section-title" style="margin-top:24px;">Account</div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-input" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-input" name="password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" class="form-input" name="password_confirmation" required>
                    </div>
                </div>

                <button type="submit" class="btn" style="margin-top:8px;">Start 14-Day Free Trial</button>
                <p style="font-size:12px;color:#6b7280;text-align:center;margin-top:12px;">
                    Gratis 14 hari. Verifikasi email untuk mengaktifkan akun.
                </p>
            </form>
        </div>
    </div>
</body>
</html>
