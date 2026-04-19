<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Savora Admin')</title>
    <style>
        :root { --bg:#0f0f0f; --panel:#1a1a1a; --panel2:#2d2d2d; --txt:#f5f5f5; --muted:#a3a3a3; --gold:#ffd700; --orange:#ffa500; --green:#4caf50; --red:#f44336; --blue:#2196f3; --radius:20px; --border:1px solid rgba(255,255,255,.10); }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; color:var(--txt); background:linear-gradient(180deg,#0f0f0f 0%,#1a1a1a 100%); }
        .container{ max-width:1240px; margin:0 auto; padding:0 24px 48px; }
        .header{ margin:0 -24px 16px; padding:42px 24px 20px; border-bottom:var(--border); background:linear-gradient(135deg,#1a1a1a 0%,#2d2d2d 70%,#3d3d3d 100%); }
        .head-row{ display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
        .brand{ display:flex; align-items:center; gap:14px; }
        .logo{ width:56px; height:56px; border-radius:18px; display:grid; place-items:center; font-size:24px; background:linear-gradient(135deg,var(--gold),var(--orange)); box-shadow:0 10px 24px rgba(255,215,0,.35); }
        .subtitle{ color:var(--gold); font-size:13px; margin-top:4px; letter-spacing:.8px; }
        .nav{ display:flex; gap:8px; flex-wrap:wrap; }
        .nav a{ text-decoration:none; color:#fff; border:var(--border); background:#141414; border-radius:12px; padding:9px 12px; font-size:13px; font-weight:700; }
        .nav a.active,.nav a:hover{ border-color:rgba(255,215,0,.45); color:var(--gold); }
        .notice{ padding:12px 14px; border-radius:12px; margin:0 0 14px; border:1px solid rgba(255,255,255,.16); background:rgba(255,255,255,.04); }
        .notice.success{ border-color:rgba(76,175,80,.5);} .notice.error{ border-color:rgba(244,67,54,.55);} 
        .section-title{ display:flex; align-items:center; gap:12px; margin:26px 0 12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; }
        .section-title:before{ content:''; width:4px; height:24px; border-radius:4px; background:linear-gradient(var(--gold),var(--orange)); }
        .panel{ border-radius:var(--radius); border:var(--border); background:linear-gradient(135deg,var(--panel2),var(--panel)); padding:18px; margin-bottom:16px; }
        .stats-grid{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
        .stat-card{ padding:16px; border-radius:16px; border:var(--border); background:#191919; }
        .stat-value{ font-size:30px; font-weight:800; margin:8px 0 4px; }
        .filters{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
        .filters input,.filters select,.filters button{ border:var(--border); border-radius:12px; background:#101010; color:#fff; padding:10px 12px; }
        .filters button{ cursor:pointer; background:linear-gradient(135deg,var(--gold),var(--orange)); color:#181818; font-weight:800; }
        table{ width:100%; border-collapse:collapse; } th,td{ padding:10px 8px; border-bottom:1px solid rgba(255,255,255,.08); text-align:left; vertical-align:top; }
        th{ color:var(--gold); font-size:12px; text-transform:uppercase; letter-spacing:.7px; }
        .badge{ display:inline-flex; padding:4px 9px; border-radius:999px; font-size:12px; font-weight:700; background:rgba(255,255,255,.08);} 
        .actions{ display:flex; flex-wrap:wrap; gap:6px; }
        .btn{ border:0; border-radius:10px; padding:8px 10px; font-size:12px; font-weight:700; color:#fff; cursor:pointer; }
        .btn-green{ background:linear-gradient(135deg,#4caf50,#66bb6a);} .btn-red{ background:linear-gradient(135deg,#f44336,#e57373);} .btn-orange{ background:linear-gradient(135deg,#ff9800,#ffb74d);} .btn-blue{ background:linear-gradient(135deg,#2196f3,#64b5f6);} 
        .muted{ color:var(--muted); font-size:12px; }
        .pagination{ display:flex; gap:8px; margin-top:12px; align-items:center; }
        .pagination a,.pagination span{ color:#fff; text-decoration:none; border:var(--border); border-radius:10px; padding:6px 10px; }
        @media (max-width:920px){ .stats-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); } }
    </style>
</head>
<body>
<div class="container">
    <header class="header">
        <div class="head-row">
            <div class="brand">
                <div class="logo">🛡️</div>
                <div>
                    <h2 style="margin:0;">ADMIN PANEL</h2>
                    <div class="subtitle">Savora Management System (Web)</div>
                </div>
            </div>
            <nav class="nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">Users</a>
                <a href="{{ route('admin.recipes') }}" class="{{ request()->routeIs('admin.recipes*') ? 'active' : '' }}">Recipes</a>
                <a href="{{ route('admin.logs') }}" class="{{ request()->routeIs('admin.logs*') ? 'active' : '' }}">Logs</a>
            </nav>
        </div>
    </header>

    @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
    @if(session('error') || ($error ?? null))<div class="notice error">{{ session('error') ?? $error }}</div>@endif

    @if(isset($stats))
        <div class="stats-grid" style="margin-bottom:16px;">
            <div class="stat-card"><div class="muted">Total Users</div><div class="stat-value" style="color:#66bb6a">{{ $stats['total_users'] ?? 0 }}</div></div>
            <div class="stat-card"><div class="muted">Banned Users</div><div class="stat-value" style="color:#ef9a9a">{{ $stats['banned_users'] ?? 0 }}</div></div>
            <div class="stat-card"><div class="muted">Pending Recipes</div><div class="stat-value" style="color:#ffb74d">{{ $stats['pending_recipes'] ?? 0 }}</div></div>
            <div class="stat-card"><div class="muted">Total Recipes</div><div class="stat-value" style="color:#ba68c8">{{ $stats['total_recipes'] ?? 0 }}</div></div>
        </div>
    @endif

    @yield('content')
</div>
@yield('scripts')
</body>
</html>