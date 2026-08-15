<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>{{ $title ?? 'Dashboard' }} — CostControl</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f4f6f9;
            --surface: #ffffff;
            --border: #e8ecf1;
            --border-strong: #d5dbe3;
            --text: #0f172a;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --primary-soft: #dbeafe;
            --success: #059669;
            --success-light: #ecfdf5;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --warning: #d97706;
            --warning-light: #fffbeb;
            --info: #0284c7;
            --info-light: #f0f9ff;
            --sidebar-w: 264px;
            --sidebar-bg: #0b1220;
            --sidebar-text: #94a3b8;
            --sidebar-hover: #162032;
            --radius: 14px;
            --radius-sm: 10px;
            --radius-xs: 8px;
            --shadow: 0 1px 2px rgba(15,23,42,0.04), 0 1px 3px rgba(15,23,42,0.06);
            --shadow-md: 0 8px 24px rgba(15,23,42,0.08);
            --shadow-lg: 0 16px 40px rgba(15,23,42,0.12);
            --transition: 0.15s ease;
            --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        img { max-width: 100%; display: block; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed; inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease;
        }
        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-mark {
            width: 38px; height: 38px;
            border-radius: 11px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: grid; place-items: center;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 8px 16px rgba(37,99,235,0.35);
            flex-shrink: 0;
        }
        .brand-text h1 {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .brand-text small {
            font-size: 11px;
            color: var(--sidebar-text);
        }
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 14px 12px 20px;
        }
        .sidebar-section { margin-bottom: 18px; }
        .sidebar-section-title {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(148,163,184,0.55);
            padding: 0 10px;
            margin-bottom: 6px;
        }
        .sidebar-acc {
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            background: none;
            border: 0;
            color: rgba(148,163,184,0.55);
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 6px 10px;
            cursor: pointer;
            border-radius: var(--radius-xs);
            transition: color 0.15s ease;
        }
        .sidebar-acc:hover { color: #cbd5e1; }
        .sidebar-acc .chev { transition: transform 0.18s ease; font-size: 11px; }
        .sidebar-section.open .sidebar-acc .chev { transform: rotate(90deg); }
        .sidebar-section-body { display: none; }
        .sidebar-section.open .sidebar-section-body { display: block; }
        .nav-item { margin-bottom: 2px; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 12px;
            border-radius: var(--radius-xs);
            color: var(--sidebar-text);
            font-size: 13.5px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            text-align: left;
        }
        .nav-link:hover { background: var(--sidebar-hover); color: #e2e8f0; }
        .nav-link.active {
            background: rgba(37,99,235,0.18);
            color: #93c5fd;
            box-shadow: inset 3px 0 0 #3b82f6;
        }
        .nav-link i { font-size: 16px; width: 20px; text-align: center; opacity: 0.9; }
        .sidebar-footer {
            padding: 14px 12px 16px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: var(--radius-xs);
            background: rgba(255,255,255,0.03);
        }
        .sidebar-user .avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            display: grid; place-items: center;
            font-weight: 600;
            font-size: 13px;
            flex-shrink: 0;
        }
        .sidebar-user .meta { min-width: 0; flex: 1; }
        .sidebar-user .name {
            font-size: 12.5px;
            font-weight: 600;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user .role {
            font-size: 11px;
            color: var(--sidebar-text);
        }

        /* ===== MAIN ===== */
        .main { margin-left: var(--sidebar-w); min-height: 100vh; }
        .topbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            gap: 16px;
        }
        .topbar-left { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .topbar-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-muted);
            flex-wrap: wrap;
        }
        .breadcrumb a { color: var(--text-secondary); }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb .sep { opacity: 0.5; }
        .breadcrumb .current { color: var(--text); font-weight: 500; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .topbar-search {
            position: relative;
            width: 240px;
        }
        .topbar-search i {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            pointer-events: none;
        }
        .topbar-search input {
            width: 100%;
            padding: 8px 12px 8px 34px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--bg);
            font-size: 13px;
            outline: none;
            transition: var(--transition);
        }
        .topbar-search input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .content { padding: 24px 28px 40px; max-width: 1400px; }

        /* ===== PAGE HEADER ===== */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }
        .page-header h2 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.25;
        }
        .page-header p {
            font-size: 13.5px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        .page-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* ===== TOOLBAR ===== */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .toolbar-left, .toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .search-box {
            position: relative;
            min-width: 220px;
        }
        .search-box i {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
        }
        .search-box input {
            width: 100%;
            padding: 9px 12px 9px 36px;
            border: 1px solid var(--border);
            border-radius: var(--radius-xs);
            background: var(--surface);
            font-size: 13px;
            outline: none;
            transition: var(--transition);
        }
        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        /* ===== SEGMENT / TABS ===== */
        .seg {
            display: inline-flex;
            flex-wrap: wrap;
            background: #eef2f7;
            border-radius: 999px;
            padding: 3px;
            gap: 2px;
        }
        .seg a, .seg button {
            border: none;
            background: transparent;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }
        .seg a:hover, .seg button:hover { color: var(--text); }
        .seg a.active, .seg button.active {
            background: #fff;
            color: var(--text);
            box-shadow: var(--shadow);
        }
        .tabs {
            display: flex;
            gap: 4px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
            overflow-x: auto;
        }
        .tab {
            padding: 11px 16px;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text-secondary);
            border-bottom: 2px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            transition: var(--transition);
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
        }
        .tab:hover { color: var(--text); }
        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .tab .count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #eef2f7;
            color: var(--text-secondary);
            font-size: 11px;
            font-weight: 600;
            margin-left: 6px;
        }
        .tab.active .count {
            background: var(--primary-light);
            color: var(--primary);
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .card-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .card-header h3 {
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-header h3 i { color: var(--text-muted); }
        .card-body { padding: 18px; }
        .card-body.compact { padding: 0; }
        .card-footer {
            padding: 12px 18px;
            border-top: 1px solid var(--border);
            background: #fafbfc;
            border-radius: 0 0 var(--radius) var(--radius);
        }

        /* ===== KPI ===== */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        .kpi-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        .kpi-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }
        .kpi-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .kpi-icon {
            width: 40px; height: 40px;
            border-radius: 11px;
            display: grid; place-items: center;
            font-size: 17px;
        }
        .kpi-icon.blue { background: var(--primary-light); color: var(--primary); }
        .kpi-icon.green { background: var(--success-light); color: var(--success); }
        .kpi-icon.red { background: var(--danger-light); color: var(--danger); }
        .kpi-icon.yellow { background: var(--warning-light); color: var(--warning); }
        .kpi-icon.slate { background: #f1f5f9; color: #475569; }
        .kpi-label {
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.2;
            word-break: break-word;
        }
        .kpi-change {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .kpi-change.up { color: var(--danger); }
        .kpi-change.down { color: var(--success); }
        .kpi-change.neutral { color: var(--text-muted); }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 14px;
            border-radius: var(--radius-xs);
            font-size: 13px;
            font-weight: 550;
            border: 1px solid transparent;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
            line-height: 1.2;
            background: #fff;
            color: var(--text);
            text-decoration: none;
        }
        .btn:disabled { opacity: 0.55; cursor: not-allowed; }
        .btn-primary {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 1px 2px rgba(37,99,235,0.25);
        }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .btn-success {
            background: var(--success);
            color: #fff;
            border-color: var(--success);
        }
        .btn-success:hover { background: #047857; }
        .btn-danger {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
        }
        .btn-outline {
            background: #fff;
            border-color: var(--border-strong);
            color: var(--text);
        }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }
        .btn-ghost {
            background: transparent;
            border-color: transparent;
            color: var(--text-secondary);
        }
        .btn-ghost:hover { background: #f1f5f9; color: var(--text); }
        .btn-sm { padding: 7px 11px; font-size: 12.5px; }
        .btn-xs { padding: 5px 8px; font-size: 12px; border-radius: 7px; }
        .btn-icon {
            width: 40px; height: 40px;
            padding: 0;
        }
        .btn-icon.btn-xs { width: 36px; height: 36px; }
        .btn-group { display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; }

        /* ===== FORMS ===== */
        .form-group { margin-bottom: 14px; }
        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 550;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-label .req { color: var(--danger); }
        .form-hint {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 5px;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-xs);
            background: #fff;
            font-size: 13.5px;
            color: var(--text);
            outline: none;
            transition: var(--transition);
        }
        .form-textarea { min-height: 88px; resize: vertical; }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .form-input::placeholder, .form-textarea::placeholder { color: #adb5c2; }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        .input-prefix {
            position: relative;
        }
        .input-prefix > span {
            position: absolute;
            left: 12px; top: 50%;
            transform: translateY(-50%);
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-muted);
            pointer-events: none;
        }
        .input-prefix .form-input { padding-left: 40px; }

        /* ===== TABLE ===== */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            text-align: left;
            padding: 11px 16px;
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            background: #fafbfc;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        tbody td {
            padding: 13px 16px;
            font-size: 13.5px;
            border-bottom: 1px solid #f1f4f8;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: background 0.1s; }
        tbody tr:hover { background: #f8fafc; }
        tbody tr.clickable { cursor: pointer; }
        tfoot tr td {
            border-top: 2px solid #e5e7eb;
            padding: 10px 16px;
            background: #f9fafb;
            font-size: 13.5px;
        }
        tfoot tr:hover { background: #f9fafb; }
        #confirmOverlay.show { display: flex !important; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .money { font-variant-numeric: tabular-nums; font-weight: 600; font-feature-settings: "tnum"; }
        .money.positive { color: var(--success); }
        .money.negative { color: var(--danger); }
        .cell-title { font-weight: 600; color: var(--text); }
        .cell-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ===== BADGE ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 600;
            line-height: 1.4;
            white-space: nowrap;
        }
        .badge-blue { background: var(--primary-light); color: var(--primary); }
        .badge-green { background: var(--success-light); color: var(--success); }
        .badge-red { background: var(--danger-light); color: var(--danger); }
        .badge-yellow { background: var(--warning-light); color: var(--warning); }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .status-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.active { background: var(--success); box-shadow: 0 0 0 3px rgba(5,150,105,0.15); }
        .status-dot.archived { background: #94a3b8; }

        /* ===== ALERT / TOAST ===== */
        .alert {
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: var(--success-light); color: #047857; border: 1px solid #a7f3d0; }
        .alert-danger { background: var(--danger-light); color: #b91c1c; border: 1px solid #fecaca; }
        .alert-info { background: var(--info-light); color: #0369a1; border: 1px solid #bae6fd; }
        .alert-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            opacity: 0.55;
            font-size: 16px;
            color: inherit;
            line-height: 1;
        }
        .alert-close:hover { opacity: 1; }
        .toast-wrap {
            position: fixed;
            top: 18px; right: 18px;
            z-index: 3000;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 360px;
        }
        .toast {
            background: #0f172a;
            color: #fff;
            padding: 12px 14px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: toastIn 0.2s ease;
        }
        .toast.success { background: #065f46; }
        .toast.error { background: #991b1b; }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: none; }
        }

        /* ===== MODAL ===== */
        .modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(3px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.15s ease;
        }
        .modal-backdrop.show {
            display: flex;
            opacity: 1;
        }
        .modal {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            max-height: calc(100vh - 40px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-lg);
            animation: modalIn 0.18s ease;
        }
        .modal.modal-lg { max-width: 640px; }
        .modal.modal-sm { max-width: 400px; }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(10px) scale(0.98); }
            to { opacity: 1; transform: none; }
        }
        .modal-header {
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .modal-header h3 {
            font-size: 15px;
            font-weight: 650;
            letter-spacing: -0.01em;
        }
        .modal-close {
            width: 32px; height: 32px;
            border: none;
            background: #f1f5f9;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            color: var(--text-secondary);
            display: grid; place-items: center;
            line-height: 1;
        }
        .modal-close:hover { background: #e2e8f0; color: var(--text); }
        .modal-body {
            padding: 18px;
            overflow-y: auto;
        }
        .modal-footer {
            padding: 14px 18px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            background: #fafbfc;
        }

        /* ===== EMPTY / MISC ===== */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 36px;
            opacity: 0.35;
            display: block;
            margin-bottom: 10px;
        }
        .empty-state p {
            font-size: 13.5px;
            margin-bottom: 12px;
        }
        .empty-state a { color: var(--primary); font-weight: 500; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .progress {
            height: 8px;
            background: #eef2f7;
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            border-radius: 999px;
            transition: width 0.35s ease;
        }
        .avatar-lg {
            width: 96px; height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: var(--shadow);
            margin: 0 auto;
        }
        .chip-filters { display: flex; gap: 8px; flex-wrap: wrap; }
        .stat-inline {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 12.5px;
            color: var(--text-secondary);
        }
        .stat-inline strong { color: var(--text); font-weight: 600; }
        .divider { height: 1px; background: var(--border); margin: 16px 0; }
        .mobile-toggle {
            display: none;
            background: none;
            border: 1px solid var(--border);
            width: 36px; height: 36px;
            border-radius: 9px;
            font-size: 18px;
            color: var(--text);
            cursor: pointer;
            place-items: center;
        }
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,23,42,0.4);
            z-index: 999;
        }
        .sidebar-overlay.show { display: block; }

        /* Scrollbar */
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        /* Responsive */
        @media (max-width: 1100px) {
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .grid-3 { grid-template-columns: 1fr 1fr; }
            .topbar-search { display: none; }
        }
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .mobile-toggle { display: grid; }
            .content { padding: 18px 16px 32px; }
            .topbar { padding: 0 16px; }
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .kpi-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: stretch; }
            .page-actions { width: 100%; }
            .page-actions .btn { flex: 1; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">CC</div>
            <div class="brand-text">
                <h1>CostControl</h1>
                <small>Manajemen Keuangan</small>
            </div>
        </div>

        <div class="sidebar-nav">
            @if(auth()->user()->isInvestor())
            {{-- Sidebar khusus investor --}}
            @php
                $investorProj = auth()->user()->investorProject()->with('project')->first();
                $invProject = $investorProj?->project;
            @endphp
            <div class="sidebar-section open">
                <div class="sidebar-section-title">Proyek Saya</div>
                <div class="sidebar-section-body" style="display:block;">
                    @if($invProject)
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cost-centers.gallery') ? 'active' : '' }}"
                           href="{{ route('cost-centers.gallery', $invProject->id_project) }}">
                            <i class="bi bi-images"></i> Galeri Proyek
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @else
            <div class="sidebar-section open">
                <button type="button" class="sidebar-acc" onclick="toggleAcc(this)">
                    Fitur Aplikasi <i class="bi bi-chevron-right chev"></i>
                </button>
                <div class="sidebar-section-body">
                    @if(! auth()->user()->isSuperAdmin())
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('beranda') ? 'active' : '' }}" href="{{ route('beranda') }}">
                            <i class="bi bi-grid-1x2"></i> Dashboard
                        </a>
                    </div>
                    @endif
                    @if(auth()->user()->isSuperAdmin())
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('super-admin.*') ? 'active' : '' }}" href="{{ route('super-admin.stats') }}">
                            <i class="bi bi-graph-up"></i> Dashboard Super Admin
                        </a>
                    </div>
                    @endif
                    @if(! auth()->user()->isSuperAdmin())
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('projects.*') || request()->routeIs('cost-centers.*') ? 'active' : '' }}" href="{{ route('cost-centers.index') }}">
                            <i class="bi bi-building"></i> @if(auth()->user()->companyModule() === 'project')
                            Unit Proyek
                        @elseif(auth()->user()->companyModule() === 'umkm')
                            Unit UMKM
                        @else
                            Unit Bisnis
                        @endif
                        </a>
                    </div>
                    @endif
                    @if(auth()->user()->isAdmin() && ! auth()->user()->isSuperAdmin())
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                            <i class="bi bi-file-earmark-bar-graph"></i> Laporan
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            @if(auth()->user()->isAdmin())
            <div class="sidebar-section open">
                <button type="button" class="sidebar-acc" onclick="toggleAcc(this)">
                    Master data <i class="bi bi-chevron-right chev"></i>
                </button>
                <div class="sidebar-section-body">
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cost-categories.*') ? 'active' : '' }}" href="{{ route('cost-categories.index') }}">
                            <i class="bi bi-folder2"></i> Kategori Biaya
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cost-types.*') ? 'active' : '' }}" href="{{ route('cost-types.index') }}">
                            <i class="bi bi-arrow-down-circle"></i> Tipe Biaya
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('income-categories.*') ? 'active' : '' }}" href="{{ route('income-categories.index') }}">
                            <i class="bi bi-folder2-open"></i> Kategori Pendapatan
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('income-types.*') ? 'active' : '' }}" href="{{ route('income-types.index') }}">
                            <i class="bi bi-arrow-up-circle"></i> Tipe Pendapatan
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('units.*') ? 'active' : '' }}" href="{{ route('units.index') }}">
                            <i class="bi bi-rulers"></i> Satuan
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('cost-groups.*') ? 'active' : '' }}" href="{{ route('cost-groups.index') }}">
                            <i class="bi bi-diagram-3"></i> Kelompok Biaya
                        </a>
                    </div>
                    @if(! auth()->user()->isSuperAdmin())
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('asset.*') ? 'active' : '' }}" href="{{ route('asset.index') }}">
                            <i class="bi bi-box-seam"></i> Aset
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if(auth()->user()->isAdmin() && ! auth()->user()->isSuperAdmin())
            <div class="sidebar-section">
                <div class="sidebar-section-title">Pengaturan</div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('perusahaan.*') ? 'active' : '' }}" href="{{ route('perusahaan.index') }}">
                        <i class="bi bi-buildings"></i> Perusahaan
                    </a>
                </div>
            </div>
            @endif
        </div>
        @endif
            <a href="{{ route('profil') }}" class="sidebar-user" style="margin-bottom:8px;">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->nama_lengkap ?? 'A', 0, 1)) }}</div>
                <div class="meta">
                    <div class="name">{{ auth()->user()->nama_lengkap }}</div>
                    <div class="role">{{ auth()->user()->role }}</div>
                </div>
                <i class="bi bi-chevron-right" style="color:var(--sidebar-text);font-size:12px;"></i>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link" style="color:#fca5a5;">
                    <i class="bi bi-box-arrow-left"></i> Keluar
                </button>
            </form>
        </div>
    </nav>

    <div class="main">
        <div class="topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" type="button" onclick="toggleSidebar()" aria-label="Menu">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    @hasSection('breadcrumb')
                        <div class="breadcrumb">@yield('breadcrumb')</div>
                    @else
                        <span class="topbar-title">{{ $title ?? 'Dashboard' }}</span>
                    @endif
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="globalSearchHint" placeholder="Cari di halaman... (/)" autocomplete="off">
                </div>
                @if(auth()->user()->isAdmin() && ! auth()->user()->isSuperAdmin() && request()->routeIs('cost-categories.index'))
                <button type="button" class="btn btn-sm btn-outline" onclick="openModal('downloadModuleModal')" title="Import modul master data dari CostControl">
                    <i class="bi bi-download"></i> Import Modul
                </button>
                @endif
                @yield('topbar-actions')
            </div>
        </div>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success" id="flashAlert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" id="flashAlert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('error') }}</span>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <div class="toast-wrap" id="toastWrap"></div>

    @if(auth()->user()->isAdmin() && ! auth()->user()->isSuperAdmin() && ! request()->routeIs('asset.index'))
    <div class="modal-backdrop" id="downloadModuleModal">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h3>Import Modul</h3>
                <button type="button" class="modal-close" onclick="closeModal('downloadModuleModal')">×</button>
            </div>
            <form method="POST" action="{{ route('modules.download') }}">
                @csrf
                <div class="modal-body">
                    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:12px;">Pilih data master yang ingin diimport dari CostControl:</p>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;">
                        @foreach(\App\Services\MasterDataModuleService::MODULES as $key => $label)
                        <label style="display:flex;align-items:center;gap:8px;font-size:14px;cursor:pointer;">
                            <input type="checkbox" name="modules[]" value="{{ $key }}" checked>
                            <span style="flex:1;">{{ $label }}</span>
                            <span style="font-size:12px;color:var(--text-muted);">{{ $moduleCounts[$key] ?? 0 }} item</span>
                        </label>
                        @endforeach
                    </div>
                    <div style="border-top:1px solid var(--border);padding-top:14px;">
                        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:8px;">Mode import:</p>
                        <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;cursor:pointer;margin-bottom:6px;">
                            <input type="radio" name="mode" value="add" checked>
                            <span><strong>Tambah saja</strong> — hanya menambah data yang belum ada, tidak mengubah atau menghapus.</span>
                        </label>
                        <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;cursor:pointer;">
                            <input type="radio" name="mode" value="update">
                            <span><strong>Perbarui &amp; selaras</strong> — perbarui data yang sudah ada dan hapus yang tidak ada di modul (hanya yang tidak terpakai).</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal('downloadModuleModal')">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-download"></i> Import</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Custom confirm dialog (must be before script) --}}
    <div id="confirmOverlay" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:14px;box-shadow:0 8px 40px rgba(0,0,0,.18);padding:28px 28px 22px;min-width:320px;max-width:420px;width:90%;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                <span style="width:36px;height:36px;background:#fff1f2;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-exclamation-triangle-fill" style="color:#e11d48;font-size:16px;"></i>
                </span>
                <strong id="confirmTitle" style="font-size:15px;color:#111827;">Konfirmasi</strong>
            </div>
            <p id="confirmMsg" style="font-size:13.5px;color:#4b5563;margin:0 0 22px;line-height:1.5;padding-left:46px;"></p>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button id="confirmCancel" class="btn btn-outline" style="min-width:80px;">Batal</button>
                <button id="confirmOk" class="btn btn-danger" style="min-width:90px;">Hapus</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('show');
        }

        function toggleAcc(btn) {
            btn.closest('.sidebar-section').classList.toggle('open');
        }

        function openModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('show');
            document.body.style.overflow = 'hidden';
            const focusable = el.querySelector('input:not([type=hidden]), select, textarea');
            if (focusable) setTimeout(() => focusable.focus(), 50);
        }
        function closeModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('show');
            if (!document.querySelector('.modal-backdrop.show')) {
                document.body.style.overflow = '';
            }
        }

        document.querySelectorAll('.modal-backdrop').forEach(el => {
            el.addEventListener('click', function(e) {
                if (e.target === this) closeModal(this.id);
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const open = document.querySelector('.modal-backdrop.show');
                if (open) closeModal(open.id);
                else closeSidebar();
            }
            if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                const local = document.querySelector('[data-table-search]');
                const global = document.getElementById('globalSearchHint');
                (local || global)?.focus();
            }
        });

        // Live table search
        document.querySelectorAll('[data-table-search]').forEach(input => {
            const target = document.querySelector(input.dataset.tableSearch);
            if (!target) return;
            input.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                target.querySelectorAll('tbody tr[data-search]').forEach(row => {
                    const hay = row.dataset.search.toLowerCase();
                    row.style.display = !q || hay.includes(q) ? '' : 'none';
                });
            });
        });
        document.getElementById('globalSearchHint')?.addEventListener('input', function() {
            const local = document.querySelector('[data-table-search]');
            if (local) {
                local.value = this.value;
                local.dispatchEvent(new Event('input'));
            }
        });

        // Auto-hide flash
        setTimeout(() => {
            const flash = document.getElementById('flashAlert');
            if (flash) flash.style.opacity = '0';
            setTimeout(() => flash?.remove(), 300);
        }, 4000);

        // Custom confirm dialog
        let _confirmResolve = null;
        const _confirmOverlay = document.getElementById('confirmOverlay');
        function showConfirm(msg, opts = {}) {
            return new Promise(resolve => {
                _confirmResolve = resolve;
                document.getElementById('confirmMsg').textContent = msg;
                document.getElementById('confirmTitle').textContent = opts.title || 'Konfirmasi';
                const btn = document.getElementById('confirmOk');
                btn.textContent = opts.ok || 'Hapus';
                btn.className = 'btn ' + (opts.okClass || 'btn-danger');
                _confirmOverlay.style.display = 'flex';
                document.getElementById('confirmOk').focus();
            });
        }
        function hideConfirm(result) {
            _confirmOverlay.style.display = 'none';
            _confirmResolve && _confirmResolve(result);
            _confirmResolve = null;
        }
        document.getElementById('confirmOk').addEventListener('click', () => hideConfirm(true));
        document.getElementById('confirmCancel').addEventListener('click', () => hideConfirm(false));
        _confirmOverlay.addEventListener('click', function(e) {
            if (e.target === this) hideConfirm(false);
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && _confirmOverlay.style.display === 'flex') {
                hideConfirm(false);
            }
        });

        // Confirm helper for destructive actions — event delegation so works on all forms incl. @@foreach
        document.addEventListener('submit', function(e) {
            const form = e.target.closest('form[data-confirm]');
            if (!form) return;
            if (form._confirmPassed) { form._confirmPassed = false; return; }
            e.preventDefault();
            e.stopImmediatePropagation();
            showConfirm(form.dataset.confirm, {
                title: form.dataset.confirmTitle || 'Konfirmasi',
                ok: form.dataset.confirmOk || 'Ya, lanjutkan',
                okClass: form.dataset.confirmClass || 'btn-danger',
            }).then(ok => {
                if (ok) { form._confirmPassed = true; form.submit(); }
            });
        }, true); // capture phase ensures we intercept before other handlers

        // Money input formatting (Indonesian)
        function formatMoneyInput(el) {
            let v = el.value.replace(/[^\d]/g, '');
            if (!v) { el.value = ''; return; }
            el.value = Number(v).toLocaleString('id-ID');
        }
        document.querySelectorAll('[data-money]').forEach(el => {
            el.addEventListener('input', () => formatMoneyInput(el));
            el.addEventListener('blur', () => formatMoneyInput(el));
            if (el.value) formatMoneyInput(el);
        });
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                this.querySelectorAll('[data-money]').forEach(el => {
                    el.value = el.value.replace(/\./g, '');
                });
            });
        });

        // Toast
        function showToast(msg, type = 'success') {
            const wrap = document.getElementById('toastWrap');
            const t = document.createElement('div');
            t.className = 'toast ' + type;
            t.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i><span>${msg}</span>`;
            wrap.appendChild(t);
            setTimeout(() => t.remove(), 3200);
        }
    </script>
    @stack('scripts')
</body>
</html>
