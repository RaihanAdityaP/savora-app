<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Savora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
        --bg:#0F0F0F;--bc:#1E1E1E;--bc2:#2A2A2A;--bi:#181818;
        --go:#FFD700;--go2:#FFA500;
        --re:#F44336;--re2:#E57373;
        --gr:#4CAF50;--gr2:#66BB6A;
        --bd:rgba(255,255,255,.07);
        --tw:#FFFFFF;--tm:#AFAFAF;--td:#5E5E5E;
    }
    html,body{height:100%;background:var(--bg);color:var(--tw);font-family:'DM Sans',sans-serif;font-size:14px}
    body{display:flex;align-items:center;justify-content:center;padding:20px;
         background-image:radial-gradient(ellipse at 20% 50%,rgba(255,215,0,.04) 0%,transparent 60%),
                          radial-gradient(ellipse at 80% 20%,rgba(255,165,0,.03) 0%,transparent 50%)}

    .card{width:100%;max-width:420px;background:linear-gradient(135deg,var(--bc2),var(--bc));
          border:1px solid var(--bd);border-radius:28px;padding:40px 36px;
          box-shadow:0 0 60px rgba(255,215,0,.06),0 24px 60px rgba(0,0,0,.4)}

    .brand{display:flex;align-items:center;gap:14px;margin-bottom:36px}
    .logo{width:52px;height:52px;border-radius:16px;
          background:linear-gradient(135deg,var(--go),var(--go2));
          display:flex;align-items:center;justify-content:center;
          box-shadow:0 0 28px rgba(255,215,0,.3);flex-shrink:0}
    .logo svg{width:26px;height:26px;fill:none;stroke:#000;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round}
    .brand-txt h1{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;letter-spacing:2.5px;
                  background:linear-gradient(90deg,var(--go),var(--go2));
                  -webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1.1}
    .brand-txt p{font-size:12px;color:var(--td);margin-top:3px}

    h2{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;margin-bottom:6px}
    .sub{font-size:13px;color:var(--td);margin-bottom:32px}

    .alert{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:12px;
           font-size:13px;font-weight:500;margin-bottom:24px}
    .alert svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2.3;
               stroke-linecap:round;stroke-linejoin:round;flex-shrink:0}
    .alert-err{background:rgba(244,67,54,.1);border:1px solid rgba(244,67,54,.25);color:var(--re2)}
    .alert-ok{background:rgba(76,175,80,.1);border:1px solid rgba(76,175,80,.25);color:var(--gr2)}

    .field{margin-bottom:20px}
    label{display:block;font-size:12px;font-weight:600;color:var(--tm);margin-bottom:7px;letter-spacing:.3px}
    .inp-wrap{position:relative}
    .inp-wrap>svg:first-child{position:absolute;left:14px;top:50%;transform:translateY(-50%);
                  width:16px;height:16px;fill:none;stroke:var(--td);stroke-width:2;
                  stroke-linecap:round;stroke-linejoin:round;pointer-events:none}
    input{width:100%;background:var(--bi);border:1px solid var(--bd);border-radius:12px;
          padding:12px 14px 12px 42px;color:var(--tw);font-family:'DM Sans',sans-serif;
          font-size:14px;outline:none;transition:border-color .2s,box-shadow .2s}
    input:focus{border-color:rgba(255,215,0,.4);box-shadow:0 0 0 3px rgba(255,215,0,.06)}
    input::placeholder{color:var(--td)}

    .toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);
               background:none;border:none;cursor:pointer;padding:4px;
               color:var(--td);transition:color .15s}
    .toggle-pw:hover{color:var(--tm)}
    .toggle-pw svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;
                   stroke-linecap:round;stroke-linejoin:round;display:block}

    .btn{width:100%;padding:13px;border:none;border-radius:13px;
         background:linear-gradient(135deg,var(--go),var(--go2));
         color:#000;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:700;
         letter-spacing:.5px;cursor:pointer;transition:opacity .15s,transform .15s,box-shadow .2s;
         margin-top:8px}
    .btn:hover{opacity:.92;transform:translateY(-1px);box-shadow:0 6px 24px rgba(255,215,0,.3)}
    .btn:active{transform:none;opacity:1}
    .btn:disabled{opacity:.5;cursor:not-allowed;transform:none}

    .footer{text-align:center;margin-top:28px;font-size:12px;color:var(--td)}
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <div class="logo">
            <svg viewBox="0 0 24 24"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>
        </div>
        <div class="brand-txt">
            <h1>SAVORA</h1>
            <p>Admin Panel</p>
        </div>
    </div>

    <h2>Welcome back</h2>
    <p class="sub">Login dengan akun admin untuk melanjutkan</p>

    @if(session('error'))
    <div class="alert alert-err">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        {{ session('error') }}
    </div>
    @endif

    @if(session('status'))
    <div class="alert alert-ok">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('admin.login.post') }}" id="loginForm">
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <div class="inp-wrap">
                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <input type="email" id="email" name="email"
                       value="{{ old('email') }}"
                       placeholder="your email"
                       autocomplete="email"
                       autofocus>
            </div>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="inp-wrap">
                <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" id="password" name="password"
                       placeholder="your password"
                       autocomplete="current-password">
                <button type="button" class="toggle-pw" onclick="togglePassword()" title="Tampilkan password">
                    <svg id="eyeIcon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn" id="submitBtn">Login ke Admin Panel</button>
    </form>

    <div class="footer">Savora Admin — Restricted Access Only</div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Memverifikasi...';
});
</script>
</body>
</html>