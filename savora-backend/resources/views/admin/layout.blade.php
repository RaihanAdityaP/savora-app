<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Savora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
        --bg:#0F0F0F;--bg-s:#111111;--bc:#1E1E1E;--bc2:#2A2A2A;--bi:#181818;
        --go:#FFD700;--go2:#FFA500;
        --gr:#4CAF50;--gr2:#66BB6A;
        --re:#F44336;--re2:#E57373;
        --or:#FF9800;--or2:#FFB74D;
        --bl:#2196F3;--bl2:#64B5F6;
        --pu:#9C27B0;--pu2:#BA68C8;
        --bd:rgba(255,255,255,.07);
        --tw:#FFFFFF;--tm:#AFAFAF;--td:#5E5E5E;
        --sw:260px;--th:64px
    }
    html,body{height:100%}
    body{background:var(--bg);color:var(--tw);font-family:'DM Sans',sans-serif;font-size:14px;line-height:1.55;display:flex}
    /* SIDEBAR */
    .sb{width:var(--sw);background:var(--bg-s);border-right:1px solid var(--bd);display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;z-index:200}
    .sb-brand{display:flex;align-items:center;gap:14px;padding:22px 20px;border-bottom:1px solid var(--bd)}
    .sb-logo{width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,var(--go),var(--go2));display:flex;align-items:center;justify-content:center;box-shadow:0 0 22px rgba(255,215,0,.25);flex-shrink:0}
    .sb-logo svg{width:24px;height:24px;fill:none;stroke:#000;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
    .sbt h2{font-family:'Syne',sans-serif;font-size:16px;font-weight:800;letter-spacing:2.5px;background:linear-gradient(90deg,var(--go),var(--go2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1.1}
    .sbt p{font-size:11px;color:var(--td);margin-top:2px}
    .sb-nav{padding:14px 12px;flex:1;overflow-y:auto}
    .sb-lbl{font-size:10px;font-weight:700;letter-spacing:2px;color:var(--td);text-transform:uppercase;padding:12px 10px 8px}
    .na{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:12px;text-decoration:none;color:var(--tm);font-size:14px;font-weight:500;margin-bottom:2px;transition:background .15s,color .15s;border:1px solid transparent}
    .na:hover{background:rgba(255,255,255,.05);color:var(--tw)}
    .na.on{background:linear-gradient(135deg,rgba(255,215,0,.16),rgba(255,165,0,.07));color:var(--go);border-color:rgba(255,215,0,.2)}
    .ni{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .na.on .ni{background:linear-gradient(135deg,var(--go),var(--go2))}
    .na:not(.on) .ni{background:rgba(255,255,255,.06)}
    .ni svg{width:17px;height:17px;fill:none;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
    .na.on .ni svg{stroke:#000}
    .na:not(.on) .ni svg{stroke:var(--tm)}
    .na:not(.on):hover .ni svg{stroke:var(--tw)}
    .sb-foot{padding:16px 20px;border-top:1px solid var(--bd);display:flex;align-items:center;gap:8px}
    .dot{width:8px;height:8px;border-radius:50%;background:var(--gr);box-shadow:0 0 7px var(--gr);flex-shrink:0}
    /* MAIN */
    .main{margin-left:var(--sw);flex:1;display:flex;flex-direction:column;min-height:100vh}
    /* TOPBAR */
    .top{height:var(--th);background:rgba(10,10,10,.88);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-bottom:1px solid var(--bd);display:flex;align-items:center;padding:0 32px;position:sticky;top:0;z-index:100}
    .top-ttl{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;letter-spacing:1.5px}
    .top-r{margin-left:auto;display:flex;align-items:center;gap:18px}
    .top-dt{font-size:13px;color:var(--td)}
    .top-b{display:flex;align-items:center;gap:7px;padding:5px 14px;border-radius:20px;background:rgba(255,255,255,.05);border:1px solid var(--bd);font-size:13px;font-weight:500;color:var(--tm)}
    /* PAGE */
    .pg{padding:32px;flex:1}
    /* ALERTS */
    .al{display:flex;align-items:center;gap:12px;padding:14px 18px;border-radius:14px;font-size:13px;font-weight:500;margin-bottom:24px}
    .al svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}
    .al-ok{background:rgba(76,175,80,.1);border:1px solid rgba(76,175,80,.25);color:var(--gr2)}
    .al-err{background:rgba(244,67,54,.1);border:1px solid rgba(244,67,54,.25);color:var(--re2)}
    /* SECTION HEADER */
    .sh{display:flex;align-items:center;gap:12px;margin-bottom:20px}
    .sh-bar{width:4px;height:28px;background:linear-gradient(180deg,var(--go),var(--go2));border-radius:2px;flex-shrink:0}
    .sh-ttl{font-family:'Syne',sans-serif;font-size:17px;font-weight:700;letter-spacing:1.5px;background:linear-gradient(90deg,var(--go),var(--go2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
    /* STAT GRID */
    .sg{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:36px}
    .sc{background:linear-gradient(135deg,var(--bc2),var(--bc));border:1px solid var(--bd);border-radius:22px;padding:24px 22px;transition:transform .2s,box-shadow .25s}
    .sc:hover{transform:translateY(-3px)}
    .sc-ico{width:48px;height:48px;border-radius:15px;display:flex;align-items:center;justify-content:center;margin-bottom:18px}
    .sc-ico svg{width:24px;height:24px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
    .sc-val{font-family:'Syne',sans-serif;font-size:36px;font-weight:800;line-height:1;margin-bottom:8px}
    .sc-lbl{font-size:12px;font-weight:600;color:var(--td);letter-spacing:.4px}
    /* MENU CARDS */
    .mg{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
    .mc{background:linear-gradient(135deg,var(--bc2),var(--bc));border:1px solid var(--bd);border-radius:22px;padding:24px;display:flex;align-items:center;gap:20px;text-decoration:none;color:inherit;transition:transform .2s,box-shadow .25s}
    .mc:hover{transform:translateY(-3px)}
    .mc-ico{width:60px;height:60px;border-radius:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .mc-ico svg{width:28px;height:28px;fill:none;stroke:#fff;stroke-width:1.9;stroke-linecap:round;stroke-linejoin:round}
    .mc-t{flex:1;min-width:0}
    .mc-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;color:var(--tw);margin-bottom:4px}
    .mc-sub{font-size:13px;color:var(--td)}
    .mc-arr{width:32px;height:32px;border-radius:50%;background:rgba(255,215,0,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .mc-arr svg{width:14px;height:14px;fill:none;stroke:var(--go);stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
    /* CARD */
    .card{background:linear-gradient(135deg,var(--bc2),var(--bc));border:1px solid var(--bd);border-radius:22px;overflow:hidden}
    .card-hd{padding:20px 24px;border-bottom:1px solid var(--bd);display:flex;align-items:center;gap:16px;flex-wrap:wrap}
    /* TABLE */
    table{width:100%;border-collapse:collapse}
    thead th{text-align:left;padding:14px 22px;font-size:11px;font-weight:700;letter-spacing:1.5px;color:var(--td);text-transform:uppercase;border-bottom:1px solid var(--bd);white-space:nowrap}
    tbody td{padding:16px 22px;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
    tbody tr:last-child td{border-bottom:none}
    tbody tr:hover td{background:rgba(255,255,255,.018)}
    /* INPUTS */
    .inp,.sel{background:var(--bi);border:1px solid var(--bd);border-radius:10px;padding:9px 14px;color:var(--tw);font-family:'DM Sans',sans-serif;font-size:13px;outline:none;transition:border-color .2s}
    .inp:focus,.sel:focus{border-color:rgba(255,215,0,.35)}
    .sel{padding-right:32px;cursor:pointer;appearance:none}
    .sel option{background:#1E1E1E}
    .sbox{position:relative;display:inline-flex;align-items:center}
    .sbox>svg{position:absolute;left:12px;width:15px;height:15px;fill:none;stroke:var(--go);stroke-width:2;stroke-linecap:round;stroke-linejoin:round;pointer-events:none}
    .sbox .inp{padding-left:36px;width:230px}
    .ssel{position:relative;display:inline-flex;align-items:center}
    .ssel>svg{position:absolute;right:11px;width:13px;height:13px;fill:none;stroke:var(--td);stroke-width:2;stroke-linecap:round;stroke-linejoin:round;pointer-events:none}
    .ssel .sel{width:160px}
    /* BUTTONS */
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 18px;border-radius:11px;border:none;font-family:'DM Sans',sans-serif;font-size:12px;font-weight:700;letter-spacing:.7px;cursor:pointer;text-decoration:none;transition:transform .15s,box-shadow .15s,opacity .15s;white-space:nowrap;line-height:1}
    .btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2.3;stroke-linecap:round;stroke-linejoin:round}
    .btn:hover{transform:translateY(-1px);opacity:.92}
    .btn:active{transform:none;opacity:1}
    .btn-sm{padding:7px 13px;font-size:11px}
    .btn-go{background:linear-gradient(135deg,var(--go),var(--go2));color:#000}
    .btn-go:hover{box-shadow:0 4px 18px rgba(255,215,0,.28)}
    .btn-gr{background:linear-gradient(135deg,var(--gr),var(--gr2));color:#fff}
    .btn-gr:hover{box-shadow:0 4px 18px rgba(76,175,80,.28)}
    .btn-re{background:linear-gradient(135deg,var(--re),var(--re2));color:#fff}
    .btn-re:hover{box-shadow:0 4px 18px rgba(244,67,54,.28)}
    .btn-pu{background:linear-gradient(135deg,var(--pu),var(--pu2));color:#fff}
    .btn-pu:hover{box-shadow:0 4px 18px rgba(156,39,176,.28)}
    .btn-gh{background:rgba(255,255,255,.06);border:1px solid var(--bd);color:var(--tm)}
    .btn-gh:hover{background:rgba(255,255,255,.1);color:var(--tw)}
    /* BADGES */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;white-space:nowrap}
    .badge svg{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
    .b-banned{background:rgba(244,67,54,.15);border:1px solid rgba(244,67,54,.3);color:var(--re2)}
    .b-active{background:rgba(76,175,80,.15);border:1px solid rgba(76,175,80,.3);color:var(--gr2)}
    .b-premium{background:rgba(156,39,176,.15);border:1px solid rgba(156,39,176,.3);color:var(--pu2)}
    .b-admin{background:rgba(255,215,0,.15);border:1px solid rgba(255,215,0,.3);color:var(--go)}
    .b-pending{background:rgba(255,152,0,.15);border:1px solid rgba(255,152,0,.3);color:var(--or2)}
    .b-approved{background:rgba(76,175,80,.15);border:1px solid rgba(76,175,80,.3);color:var(--gr2)}
    .b-rejected{background:rgba(244,67,54,.15);border:1px solid rgba(244,67,54,.3);color:var(--re2)}
    /* CHIPS */
    .chips{display:flex;gap:8px;flex-wrap:wrap}
    .chip{display:inline-flex;align-items:center;padding:7px 18px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none;border:1px solid var(--bd);color:var(--td);background:var(--bc);letter-spacing:.4px;transition:all .15s}
    .chip:hover{border-color:rgba(255,215,0,.25);color:var(--tw)}
    .chip.on{background:linear-gradient(135deg,var(--go),var(--go2));color:#000;border-color:transparent}
    /* AVATAR */
    .ava{width:38px;height:38px;border-radius:50%;background:var(--bc2);border:2px solid var(--bd);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;overflow:hidden;flex-shrink:0}
    .ava img{width:100%;height:100%;object-fit:cover}
    /* RECIPE IMG */
    .rimg{width:58px;height:58px;border-radius:13px;background:var(--bc2);border:1px solid var(--bd);flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center}
    .rimg img{width:100%;height:100%;object-fit:cover}
    .rimg svg{width:24px;height:24px;fill:none;stroke:var(--td);stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round}
    /* MODAL */
    .mbk{position:fixed;inset:0;background:rgba(0,0,0,.78);backdrop-filter:blur(5px);z-index:500;display:none;align-items:center;justify-content:center;padding:20px}
    .mbk.open{display:flex}
    .modal{background:var(--bc);border:1px solid rgba(255,255,255,.1);border-radius:24px;width:100%;max-width:480px;padding:32px;animation:mIn .2s ease;max-height:90vh;overflow-y:auto}
    .modal-xl{max-width:640px}
    @keyframes mIn{from{opacity:0;transform:scale(.96) translateY(14px)}to{opacity:1;transform:none}}
    .m-head{display:flex;align-items:center;gap:14px;margin-bottom:24px}
    .m-ico{width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .m-ico svg{width:22px;height:22px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
    .m-ttl{font-family:'Syne',sans-serif;font-size:18px;font-weight:700}
    /* REASON CHIPS */
    .rchips{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0}
    .rchip{display:inline-flex;align-items:center;padding:7px 15px;border-radius:9px;border:1px solid var(--bd);background:var(--bc2);color:var(--tm);font-size:12px;font-weight:600;cursor:pointer;transition:all .15s}
    .rchip:has(input:checked){background:linear-gradient(135deg,var(--go),var(--go2));color:#000;border-color:transparent}
    .rchip input{display:none}
    /* PAGINATOR */
    .pager{display:flex;align-items:center;gap:6px;padding:18px 22px;border-top:1px solid var(--bd);flex-wrap:wrap}
    .pg-a{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;text-decoration:none;border:1px solid var(--bd);color:var(--tm);transition:all .15s}
    .pg-a:hover{border-color:rgba(255,215,0,.3);color:var(--tw)}
    .pg-a.on{background:linear-gradient(135deg,var(--go),var(--go2));color:#000;border-color:transparent}
    .pg-a.dis{opacity:.3;pointer-events:none}
    /* EMPTY */
    .empty{text-align:center;padding:64px 24px}
    .e-ico{width:88px;height:88px;border-radius:50%;background:var(--bc2);border:2px solid var(--bd);display:flex;align-items:center;justify-content:center;margin:0 auto 22px}
    .e-ico svg{width:40px;height:40px;fill:none;stroke:var(--td);stroke-width:1.4;stroke-linecap:round;stroke-linejoin:round}
    .e-ttl{font-size:18px;font-weight:700;color:var(--tm);margin-bottom:6px}
    .e-sub{font-size:13px;color:var(--td)}
    /* LOG CARD */
    .lc{background:linear-gradient(135deg,var(--bc2),var(--bc));border:1px solid var(--bd);border-radius:20px;padding:20px;display:flex;align-items:flex-start;gap:16px;margin-bottom:12px}
    .l-ico{width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .l-ico svg{width:22px;height:22px;fill:none;stroke:#fff;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
    /* FORM */
    .lbl{font-size:12px;font-weight:600;color:var(--tm);display:block;margin-bottom:6px}
    .ta{width:100%;background:var(--bc2);border:1px solid var(--bd);border-radius:11px;padding:11px 14px;color:var(--tw);font-family:'DM Sans',sans-serif;font-size:13px;resize:vertical;outline:none;transition:border-color .2s}
    .ta:focus{border-color:rgba(255,215,0,.35)}
    /* UTILS */
    .f{display:flex}.ac{align-items:center}.jb{justify-content:space-between}.g2{gap:8px}.g3{gap:12px}.g4{gap:16px}.g5{gap:20px}
    .mla{margin-left:auto}.mb2{margin-bottom:8px}.mb3{margin-bottom:12px}.mb4{margin-bottom:16px}.mb5{margin-bottom:20px}.mb6{margin-bottom:24px}
    .mt4{margin-top:16px}.mt5{margin-top:20px}.wf{width:100%}.tr{text-align:right}
    .tc{color:var(--td)}.tm{color:var(--tm)}.ts{font-size:12px}.tx{font-size:11px}.fw7{font-weight:700}.fw6{font-weight:600}
    .trunc{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    @media(max-width:1100px){.sg{grid-template-columns:repeat(2,1fr)}.mg{grid-template-columns:1fr}}
    </style>
    @stack('styles')
</head>
<body>
<aside class="sb">
    <div class="sb-brand">
        <div class="sb-logo">
            <svg viewBox="0 0 24 24"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
        </div>
        <div class="sbt">
            <h2>SAVORA</h2>
            <p>Admin Panel</p>
        </div>
    </div>
    <nav class="sb-nav">
        <div class="sb-lbl">Management</div>
        <a href="{{ route('admin.dashboard') }}" class="na {{ request()->routeIs('admin.dashboard') ? 'on' : '' }}">
            <div class="ni"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg></div>
            Dashboard
        </a>
        <a href="{{ route('admin.users') }}" class="na {{ request()->routeIs('admin.users') ? 'on' : '' }}">
            <div class="ni"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            Users
        </a>
        <a href="{{ route('admin.recipes') }}" class="na {{ request()->routeIs('admin.recipes') ? 'on' : '' }}">
            <div class="ni"><svg viewBox="0 0 24 24"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg></div>
            Recipes
            @if(($pendingRecipeCount ?? 0) > 0)
                <span style="margin-left:auto;background:var(--re);color:#fff;border-radius:20px;padding:1px 8px;font-size:10px;font-weight:700;">{{ $pendingRecipeCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.tags') }}" class="na {{ request()->routeIs('admin.tags') ? 'on' : '' }}">
            <div class="ni">
                <svg viewBox="0 0 24 24">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                </svg>
            </div>
            Tags
            @if(($pendingTagCount ?? 0) > 0)
                <span style="margin-left:auto;background:var(--re);color:#fff;border-radius:20px;padding:1px 8px;font-size:10px;font-weight:700;">{{ $pendingTagCount }}</span>
            @endif
        </a>
        <a href="{{ route('admin.logs') }}" class="na {{ request()->routeIs('admin.logs') ? 'on' : '' }}">
            <div class="ni"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
            Activity Logs
        </a>
    </nav>
    <div class="sb-foot">
        <div class="dot"></div>
        <span class="ts tc">System Online</span>
    </div>
</aside>
<div class="main">
    <header class="top">
        <div class="top-ttl">@yield('page-title','DASHBOARD')</div>
        <div class="top-r">
    <span class="top-dt">{{ now()->format('d M Y') }}</span>
    <div class="top-b"><div class="dot"></div>{{ session('admin_username', 'Admin') }}</div>
    <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
        @csrf
        <button type="button"
                onclick="if(confirm('Logout dari admin panel?')) this.closest('form').submit()"
                style="display:flex;align-items:center;gap:6px;padding:6px 14px;border-radius:20px;
                       background:rgba(244,67,54,.1);border:1px solid rgba(244,67,54,.2);
                       color:#E57373;font-family:'DM Sans',sans-serif;font-size:13px;
                       font-weight:500;cursor:pointer;transition:all .15s;"
                onmouseover="this.style.background='rgba(244,67,54,.18)'"
                onmouseout="this.style.background='rgba(244,67,54,.1)'">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
        </button>
    </form>
</div>
    </header>
    <main class="pg">
        @if(session('status'))
        <div class="al al-ok"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>{{ session('status') }}</div>
        @endif
        @if(session('error'))
        <div class="al al-err"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html> 