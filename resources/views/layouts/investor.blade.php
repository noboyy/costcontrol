<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal Investor') — Sahla Journey</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #f2f7f4;
            --surface: #ffffff;
            --line: #e2ede7;
            --line-soft: #edf4f0;
            --ink: #0c1f1a;
            --muted: #5c736d;
            --muted-2: #8aa39c;
            --accent: #0e9f6e;
            --accent-dark: #0a7d56;
            --accent-soft: #e6f5ee;
            --up: #0e9f6e;
            --down: #dc2f4b;
            --amber: #d98a17;
            --radius: 14px;
        }
        html { -webkit-text-size-adjust: 100%; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--ink);
            font-size: 14.5px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }

        /* Topbar */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            position: sticky; top: 0; z-index: 40;
        }
        .topbar-inner {
            max-width: 1020px; margin: 0 auto;
            padding: 12px 22px;
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 15.5px; letter-spacing: -0.01em; text-decoration: none; }
        .brand i { color: var(--accent); font-size: 20px; }
        .brand small { display: block; font-weight: 500; font-size: 10.5px; color: var(--muted-2); letter-spacing: 0.04em; text-transform: uppercase; }
        .top-actions { display: flex; align-items: center; gap: 4px; }
        .top-link { color: var(--muted); text-decoration: none; font-size: 13px; font-weight: 500; padding: 7px 10px; border-radius: 9px; }
        .top-link:hover { color: var(--accent); background: var(--accent-soft); }

        /* Pill nav */
        .pills { display: flex; flex-wrap: wrap; gap: 8px; }
        .pill {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 15px; border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--muted);
            font-size: 13px; font-weight: 600; text-decoration: none;
            transition: .15s ease;
        }
        .pill i { font-size: 13px; }
        .pill:hover { border-color: #cfe3d9; color: var(--accent-dark); }
        .pill.active { background: var(--accent); border-color: var(--accent); color: #fff; box-shadow: 0 6px 16px -8px rgba(14,159,110,.6); }

        /* Layout */
        .wrap { max-width: 1020px; margin: 0 auto; padding: 30px 22px 72px; }

        /* Head */
        .head { margin-bottom: 22px; }
        .head-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
        .h1 { font-size: 25px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.2; }
        .meta { margin-top: 6px; color: var(--muted); font-size: 13px; }
        .meta .sep { margin: 0 7px; color: var(--muted-2); }

        .status { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; letter-spacing: .02em; padding: 3px 10px; border-radius: 999px; }
        .status-live { background: var(--accent-soft); color: var(--accent-dark); }
        .status-arch { background: #eef1f0; color: var(--muted); }

        /* Cards & hero */
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); padding: 20px 22px; }
        .card + .card { margin-top: 16px; }
        .grid-hero { display: grid; grid-template-columns: 1.15fr .85fr; gap: 16px; }
        .grid-hero .card { margin-top: 0; }
        .hero-label { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted-2); }
        .hero-num { font-size: 34px; font-weight: 800; letter-spacing: -0.03em; font-variant-numeric: tabular-nums; line-height: 1.15; margin-top: 8px; }
        .hero-sub { margin-top: 8px; font-size: 12.5px; color: var(--muted); }
        .pos { color: var(--up); }
        .neg { color: var(--down); }
        .muted { color: var(--muted); }
        .num { font-variant-numeric: tabular-nums; font-weight: 600; }

        .kv { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; font-size: 13.5px; }
        .kv + .kv { margin-top: 10px; }
        .kv .k { color: var(--muted); }
        .kv .v { font-weight: 700; font-variant-numeric: tabular-nums; }
        .kv-line { padding-bottom: 10px; border-bottom: 1px dashed var(--line); }
        .kv-line + .kv-line { margin-top: 10px; }

        /* Alerts */
        .alertb { display: flex; gap: 10px; align-items: flex-start; font-size: 13.5px; padding: 12px 15px; border-radius: 11px; border: 1px solid; margin-bottom: 16px; }
        .alertb i { margin-top: 1px; }
        .alertb-danger { background: #fdf0f2; border-color: #f6cfd6; color: #a91c35; }
        .alertb-info { background: var(--accent-soft); border-color: #cde9dd; color: var(--accent-dark); }

        /* Section */
        .sec { margin-top: 26px; }
        .sec-title { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--muted-2); margin-bottom: 10px; }
        .cols-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .cols-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .cols-3 .card { margin-top: 0; }

        /* Mini stat */
        .mini-label { font-size: 12px; color: var(--muted); }
        .mini-val { font-size: 19px; font-weight: 800; letter-spacing: -0.01em; font-variant-numeric: tabular-nums; margin-top: 2px; }
        .mini-sub { font-size: 11.5px; color: var(--muted-2); margin-top: 2px; }

        /* Progress */
        .track { height: 9px; background: #e6efe9; border-radius: 999px; overflow: hidden; }
        .track > i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #18b57f, var(--accent)); }
        .budget-meta { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; color: var(--muted); margin-bottom: 8px; flex-wrap: wrap; }
        .budget-meta strong { color: var(--ink); font-variant-numeric: tabular-nums; }

        /* Breakdown list */
        .blist { display: flex; flex-direction: column; }
        .brow { display: grid; grid-template-columns: 1fr auto; align-items: baseline; gap: 14px; padding: 8px 0; border-bottom: 1px solid var(--line-soft); }
        .brow:last-child { border-bottom: none; }
        .bname { font-size: 13.5px; }
        .bamt { font-size: 13.5px; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .bamt small { color: var(--muted-2); font-weight: 500; font-size: 11px; }
        .bbar { grid-column: 1 / -1; height: 4px; background: #eef4f1; border-radius: 999px; margin-top: -2px; }
        .bbar > i { display: block; height: 100%; border-radius: 999px; }

        /* Filters & buttons */
        .filter { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
        .field { display: flex; flex-direction: column; gap: 4px; }
        .field label { font-size: 11px; font-weight: 600; color: var(--muted-2); text-transform: uppercase; letter-spacing: .04em; }
        .finput {
            border: 1px solid var(--line); background: var(--surface);
            border-radius: 10px; padding: 8px 11px; font-size: 13px; font-family: inherit; color: var(--ink);
        }
        .finput:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(14,159,110,.12); }
        .btn { display: inline-flex; align-items: center; gap: 7px; border: none; border-radius: 10px; padding: 9px 15px; font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer; text-decoration: none; }
        .btn-pri { background: var(--accent); color: #fff; }
        .btn-pri:hover { background: var(--accent-dark); }
        .btn-ghost { background: var(--surface); color: var(--muted); border: 1px solid var(--line); }
        .btn-ghost:hover { color: var(--accent-dark); border-color: #cfe3d9; }

        /* Summary line */
        .summary { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; flex-wrap: wrap; color: var(--muted); font-size: 13px; margin-bottom: 12px; }
        .summary strong { color: var(--ink); font-variant-numeric: tabular-nums; }

        /* Table */
        .table-min { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .table-min thead th {
            text-align: left; font-size: 11px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
            color: var(--muted-2); padding: 8px 12px; border-bottom: 1px solid var(--line);
        }
        .table-min thead th.num { text-align: right; }
        .table-min tbody td { padding: 11px 12px; border-bottom: 1px solid var(--line-soft); vertical-align: top; }
        .table-min tbody tr:last-child td { border-bottom: none; }
        .table-min .num { text-align: right; white-space: nowrap; }
        .table-min tfoot td { padding: 10px 12px; border-top: 1px solid var(--line); font-weight: 700; }
        .cell { font-weight: 600; }
        .cell-sub { font-size: 12px; color: var(--muted); font-weight: 500; margin-top: 1px; }
        .cell-ital { font-size: 12px; color: var(--muted-2); font-style: italic; margin-top: 2px; }
        .chip { display: inline-block; font-size: 11px; font-weight: 600; color: #3f5b53; background: #eef4f1; padding: 2px 9px; border-radius: 999px; }
        .chip.less { font-size: 11px; font-weight: 600; }

        /* Bukti thumbs */
        .thumbs { display: flex; gap: 6px; flex-wrap: wrap; }
        .thumb { display: inline-flex; width: 32px; height: 32px; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid var(--line); overflow: hidden; text-decoration: none; }
        .thumb img { width: 100%; height: 100%; object-fit: cover; }
        .thumb.ic { background: #f6faf8; color: var(--muted-2); font-size: 13px; }

        .pct { color: var(--muted-2); font-weight: 500; font-size: 12px; }
        .empty { text-align: center; padding: 40px 16px; color: var(--muted); }
        .empty i { font-size: 30px; color: var(--muted-2); display: block; margin-bottom: 10px; }

        .subtle-note { font-size: 12px; color: var(--muted-2); margin-top: 8px; }
        .endnote { text-align: center; color: var(--muted-2); font-size: 12px; margin-top: 40px; }

        @media (max-width: 760px) {
            .wrap { padding: 22px 16px 60px; }
            .h1 { font-size: 21px; }
            .grid-hero, .cols-2, .cols-3 { grid-template-columns: 1fr; }
            .hero-num { font-size: 28px; }
            .top-actions .hide-sm { display: none; }
            .table-min thead th, .table-min tbody td, .table-min tfoot td { padding: 9px 8px; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('investor.index') }}">
                <i class="bi bi-airplane"></i>
                <span>Sahla Journey<small>Portal Investor</small></span>
            </a>
            <div class="top-actions">
                <a class="top-link hide-sm" href="{{ route('profil') }}"><i class="bi bi-person"></i> Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="top-link" style="background:none;border:none;cursor:pointer;"><i class="bi bi-box-arrow-right"></i> Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="wrap">
        @if(session('error'))
        <div class="alertb alertb-danger" style="margin-bottom:16px;">
            <i class="bi bi-exclamation-triangle-fill"></i><span>{{ session('error') }}</span>
        </div>
        @endif
        @if(session('success'))
        <div class="alertb alertb-info" style="margin-bottom:16px;">
            <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
        </div>
        @endif

        @yield('content')
        <div class="endnote">© {{ date('Y') }} Sahla Journey Finance</div>
    </main>
</body>
</html>
