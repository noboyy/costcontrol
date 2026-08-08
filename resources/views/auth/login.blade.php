<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — CostControl</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            background: #f4f6f9;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        .panel-left {
            background:
                radial-gradient(ellipse at 20% 20%, rgba(59,130,246,0.35), transparent 50%),
                radial-gradient(ellipse at 80% 80%, rgba(14,165,233,0.2), transparent 45%),
                linear-gradient(160deg, #0b1220 0%, #111827 55%, #0f172a 100%);
            color: #fff;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .panel-left::after {
            content: '';
            position: absolute;
            inset: auto -20% -30% 30%;
            height: 60%;
            background: radial-gradient(circle, rgba(37,99,235,0.25), transparent 60%);
            pointer-events: none;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .brand-mark {
            width: 42px; height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #60a5fa, #2563eb);
            display: grid; place-items: center;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(37,99,235,0.4);
        }
        .brand h1 { font-size: 18px; font-weight: 700; letter-spacing: -0.02em; }
        .brand p { font-size: 12px; color: #94a3b8; margin-top: 1px; }
        .hero { position: relative; z-index: 1; max-width: 420px; }
        .hero h2 {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.2;
            margin-bottom: 14px;
        }
        .hero p {
            font-size: 14.5px;
            color: #94a3b8;
            line-height: 1.65;
        }
        .features {
            display: grid;
            gap: 12px;
            margin-top: 28px;
        }
        .feature {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
        }
        .feature i {
            width: 32px; height: 32px;
            border-radius: 9px;
            background: rgba(59,130,246,0.2);
            color: #93c5fd;
            display: grid; place-items: center;
            flex-shrink: 0;
        }
        .feature strong { display: block; font-size: 13px; margin-bottom: 2px; }
        .feature span { font-size: 12px; color: #94a3b8; }

        .panel-right {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
        }
        .login-box { width: 100%; max-width: 400px; }
        .login-box h2 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin-bottom: 6px;
        }
        .login-box > p {
            font-size: 13.5px;
            color: #64748b;
            margin-bottom: 28px;
        }
        .card {
            background: #fff;
            border: 1px solid #e8ecf1;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 8px 24px rgba(15,23,42,0.04);
        }
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 550;
            color: #334155;
            margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
        }
        .form-input {
            width: 100%;
            padding: 11px 12px 11px 38px;
            font-size: 14px;
            font-family: inherit;
            border: 1px solid #d5dbe3;
            border-radius: 10px;
            outline: none;
            transition: 0.15s ease;
            background: #fff;
        }
        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .password-toggle {
            position: absolute;
            right: 10px; top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            font-size: 15px;
        }
        .password-toggle:hover { color: #475569; }
        .btn {
            width: 100%;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            cursor: pointer;
            transition: 0.15s ease;
            margin-top: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn:hover { background: #1d4ed8; }
        .btn:active { transform: scale(0.99); }
        .alert {
            padding: 11px 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            gap: 8px;
            align-items: flex-start;
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #94a3b8;
        }
        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
            .panel-left { display: none; }
            .panel-right { min-height: 100vh; }
        }
    </style>
</head>
<body>
    <div class="panel-left">
        <div class="brand">
            <div class="brand-mark">CC</div>
            <div>
                <h1>CostControl</h1>
                <p>Manajemen Keuangan Project</p>
            </div>
        </div>
        <div class="hero">
            <h2>Kontrol biaya project lebih rapi & transparan.</h2>
            <p>Catat biaya, pantau pendapatan, dan pantau margin project dalam satu dashboard.</p>
            <div class="features">
                <div class="feature">
                    <i class="bi bi-graph-up-arrow"></i>
                    <div>
                        <strong>Dashboard real-time</strong>
                        <span>KPI biaya, pendapatan, dan tren mingguan</span>
                    </div>
                </div>
                <div class="feature">
                    <i class="bi bi-folder2-open"></i>
                    <div>
                        <strong>Project-centric</strong>
                        <span>Semua transaksi terikat ke project</span>
                    </div>
                </div>
                <div class="feature">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <strong>Multi-role aman</strong>
                        <span>Akses Super Admin & Admin terpisah</span>
                    </div>
                </div>
            </div>
        </div>
        <div style="font-size:12px;color:#64748b;position:relative;z-index:1;">© {{ date('Y') }} CostControl</div>
    </div>

    <div class="panel-right">
        <div class="login-box">
            <h2>Selamat datang</h2>
            <p>Masuk untuk melanjutkan ke dashboard</p>

            <div class="card">
                @if ($errors->any())
                    <div class="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <div class="input-wrap">
                            <i class="bi bi-person"></i>
                            <input type="text" class="form-input" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="username">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrap">
                            <i class="bi bi-lock"></i>
                            <input type="password" class="form-input" name="password" id="password" required autocomplete="current-password" placeholder="••••••••" style="padding-right:40px;">
                            <button type="button" class="password-toggle" onclick="togglePw()" aria-label="Tampilkan password">
                                <i class="bi bi-eye" id="pwIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn">
                        Masuk <i class="bi bi-arrow-right"></i>
                    </button>
                </form>
            </div>
            <div class="footer-note">Gunakan akun yang diberikan administrator</div>
        </div>
    </div>

    <script>
    function togglePw() {
        const el = document.getElementById('password');
        const icon = document.getElementById('pwIcon');
        const show = el.type === 'password';
        el.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    }
    </script>
</body>
</html>
