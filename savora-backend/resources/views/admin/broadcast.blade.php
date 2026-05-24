@extends('admin.layout')

@section('title', 'Broadcast Notification')
@section('page-title', 'BROADCAST')

@section('content')

{{-- ── Alerts ── --}}
@if(($error ?? null) && !session('error'))
<div class="al al-err">
  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>
  {{ $error }}
</div>
@endif

{{-- ── Form ── --}}
<div class="bc-grid">

  {{-- LEFT: form --}}
  <form method="POST" action="{{ route('admin.broadcast.send') }}" class="bc-form-col" id="bcForm">
    @csrf

    {{-- Audience --}}
    <div class="bc-section">
      <div class="bc-section-label">
        <div class="bc-step">1</div>
        <span>Target Audience</span>
      </div>
      <div class="bc-radio-group">
        <label class="bc-radio" id="radioAll">
          <input type="radio" name="audience" value="all" @checked(old('audience', 'all') === 'all')
                 onchange="document.getElementById('targetUser').classList.add('bc-hidden')">
          <div class="bc-radio-icon" style="background:linear-gradient(135deg,var(--gr),var(--gr2))">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div class="bc-radio-body">
            <div class="bc-radio-title">Semua User Aktif</div>
            <div class="bc-radio-desc">Kirim ke seluruh user yang tidak dibanned</div>
          </div>
          <div class="bc-radio-check">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        </label>

        <label class="bc-radio" id="radioUser">
          <input type="radio" name="audience" value="user" @checked(old('audience') === 'user')
                 onchange="document.getElementById('targetUser').classList.remove('bc-hidden')">
          <div class="bc-radio-icon" style="background:linear-gradient(135deg,var(--bl),var(--bl2))">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </div>
          <div class="bc-radio-body">
            <div class="bc-radio-title">User Tertentu</div>
            <div class="bc-radio-desc">Kirim hanya ke satu user spesifik</div>
          </div>
          <div class="bc-radio-check">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        </label>
      </div>

      <div id="targetUser" class="{{ old('audience') === 'user' ? '' : 'bc-hidden' }}" style="margin-top:12px;">
        <label class="lbl">Pilih User</label>
        <div class="ssel">
          <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
          <select name="user_id" class="sel" style="width:100%;">
            <option value="">Pilih user...</option>
            @foreach($users as $user)
              <option value="{{ $user['id'] }}" @selected(old('user_id') === $user['id'])>
                {{ $user['username'] ?? $user['full_name'] ?? $user['id'] }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    {{-- Content --}}
    <div class="bc-section">
      <div class="bc-section-label">
        <div class="bc-step">2</div>
        <span>Isi Notifikasi</span>
      </div>

      <div style="margin-bottom:14px;">
        <label class="lbl">Title <span class="bc-required">*</span></label>
        <div class="bc-input-wrap">
          <svg class="bc-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h8M4 18h16"/>
          </svg>
          <input name="title" class="inp bc-inp-padded" value="{{ old('title') }}"
                 maxlength="120" required placeholder="Contoh: Event Baru di Savora"
                 oninput="updatePreview()">
        </div>
        <div class="bc-char-hint" id="titleCount">0 / 120</div>
      </div>

      <div>
        <label class="lbl">Message <span class="bc-required">*</span></label>
        <textarea name="message" class="inp bc-textarea" rows="4" maxlength="500" required
                  placeholder="Tulis pesan notifikasi yang informatif dan menarik..."
                  oninput="updatePreview()">{{ old('message') }}</textarea>
        <div class="bc-char-hint" id="msgCount">0 / 500</div>
      </div>
    </div>

    {{-- Action --}}
    <div class="bc-section">
      <div class="bc-section-label">
        <div class="bc-step">3</div>
        <span>Aksi Tap (Opsional)</span>
      </div>
      <div class="grid" style="grid-template-columns:1fr 1fr;gap:14px;">
        <div>
          <label class="lbl">Tap Action</label>
          <div class="ssel">
            <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
            <select name="route" class="sel" style="width:100%;">
              <option value="home"    @selected(old('route') === 'home')>Home</option>
              <option value="recipe"  @selected(old('route') === 'recipe')>Recipe Detail</option>
              <option value="profile" @selected(old('route') === 'profile')>Profile</option>
            </select>
          </div>
        </div>
        <div>
          <label class="lbl">Related ID</label>
          <input name="related_entity_id" class="inp" value="{{ old('related_entity_id') }}"
                 placeholder="UUID recipe/profile">
        </div>
      </div>
    </div>

    {{-- Submit --}}
    <button type="submit" class="btn btn-go bc-submit-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 8a6 6 0 0 1 0 8"/><path d="M21 5a10 10 0 0 1 0 14"/>
        <path d="M8 8l-5 4 5 4V8z"/><path d="M10 8v8l5 4V4l-5 4z"/>
      </svg>
      Kirim Broadcast
    </button>
  </form>

  {{-- RIGHT: preview + tips --}}
  <div class="bc-right-col">

    {{-- Notif preview --}}
    <div class="bc-preview-card">
      <div class="bc-preview-label">
        <div class="bc-dot-live"></div>
        Preview Notifikasi
      </div>

      {{-- Phone frame --}}
      <div class="bc-phone">
        <div class="bc-phone-status">
          <span style="font-size:10px;color:rgba(255,255,255,.7);font-weight:600;">9:41</span>
          <div style="display:flex;align-items:center;gap:3px;">
            <div style="width:3px;height:6px;background:rgba(255,255,255,.6);border-radius:1px;"></div>
            <div style="width:3px;height:9px;background:rgba(255,255,255,.6);border-radius:1px;"></div>
            <div style="width:3px;height:12px;background:rgba(255,255,255,.9);border-radius:1px;"></div>
          </div>
        </div>
        <div class="bc-phone-screen">
          <div class="bc-notif-bubble">
            <div class="bc-notif-app">
              <div class="bc-notif-app-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 11l19-9-9 19-2-8-8-2z"/>
                </svg>
              </div>
              <span>Savora</span>
              <span style="margin-left:auto;opacity:.5;">now</span>
            </div>
            <div class="bc-notif-title" id="previewTitle">Judul notifikasi</div>
            <div class="bc-notif-msg" id="previewMsg">Isi pesan notifikasi akan muncul di sini...</div>
          </div>
        </div>
        <div class="bc-phone-home"></div>
      </div>
    </div>

    {{-- Tips --}}
    <div class="bc-tips">
      <div class="bc-tips-header">
        <svg viewBox="0 0 24 24" fill="none" stroke="var(--go)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        Tips Broadcast
      </div>
      <ul class="bc-tips-list">
        <li><span class="bc-tip-dot"></span> Judul singkat dan jelas — maks. 60 karakter ideal</li>
        <li><span class="bc-tip-dot"></span> Pesan informatif — jelaskan manfaat atau aksi yang diinginkan</li>
        <li><span class="bc-tip-dot"></span> Gunakan Related ID jika tap action mengarah ke konten spesifik</li>
        <li><span class="bc-tip-dot"></span> Test ke user tertentu sebelum blast ke semua user</li>
      </ul>
    </div>

  </div>
</div>

{{-- ── Styles ── --}}
<style>
/* Grid layout */
.bc-grid{display:grid;grid-template-columns:1fr 340px;gap:22px;align-items:start;}
@media(max-width:1100px){.bc-grid{grid-template-columns:1fr;}}

/* Form col */
.bc-form-col{display:flex;flex-direction:column;gap:16px;}
.bc-section{background:linear-gradient(135deg,var(--bc2),var(--bc));border:1px solid var(--bd);border-radius:18px;padding:20px 22px;}
.bc-section-label{display:flex;align-items:center;gap:10px;margin-bottom:18px;font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:var(--tw);letter-spacing:.5px;}
.bc-step{width:26px;height:26px;border-radius:8px;background:linear-gradient(135deg,var(--go),var(--go2));color:#000;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.bc-required{color:var(--re2);font-size:11px;}

/* Radio group */
.bc-radio-group{display:flex;flex-direction:column;gap:10px;}
.bc-radio{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:14px;border:1.5px solid var(--bd);background:var(--bi);cursor:pointer;transition:border-color .2s,background .2s;}
.bc-radio:has(input:checked){border-color:rgba(255,215,0,.35);background:rgba(255,215,0,.06);}
.bc-radio input{display:none;}
.bc-radio-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.bc-radio-icon svg{width:18px;height:18px;}
.bc-radio-body{flex:1;min-width:0;}
.bc-radio-title{font-size:13px;font-weight:700;color:var(--tw);margin-bottom:2px;}
.bc-radio-desc{font-size:11px;color:var(--td);}
.bc-radio-check{width:22px;height:22px;border-radius:50%;border:1.5px solid var(--bd);display:flex;align-items:center;justify-content:center;transition:background .2s,border-color .2s;}
.bc-radio-check svg{width:11px;height:11px;fill:none;stroke:transparent;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;transition:stroke .2s;}
.bc-radio:has(input:checked) .bc-radio-check{background:var(--go);border-color:var(--go);}
.bc-radio:has(input:checked) .bc-radio-check svg{stroke:#000;}

/* Input extras */
.bc-input-wrap{position:relative;}
.bc-input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:var(--td);pointer-events:none;}
.bc-inp-padded{padding-left:38px !important;}
.bc-textarea{width:100%;resize:vertical;min-height:110px;}
.bc-char-hint{font-size:11px;color:var(--td);text-align:right;margin-top:5px;}
.bc-hidden{display:none;}

/* Submit button */
.bc-submit-btn{width:100%;padding:14px;border-radius:14px;font-size:14px;letter-spacing:.8px;justify-content:center;gap:9px;}

/* Right col */
.bc-right-col{display:flex;flex-direction:column;gap:16px;position:sticky;top:80px;}

/* Preview card */
.bc-preview-card{background:linear-gradient(135deg,var(--bc2),var(--bc));border:1px solid var(--bd);border-radius:18px;padding:20px;overflow:hidden;}
.bc-preview-label{display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;letter-spacing:1.2px;color:var(--td);text-transform:uppercase;margin-bottom:18px;}
.bc-dot-live{width:7px;height:7px;border-radius:50%;background:#4CAF50;box-shadow:0 0 6px #4CAF50;animation:livePulse 2s ease-in-out infinite;}
@keyframes livePulse{0%,100%{opacity:1;}50%{opacity:.5;}}

/* Phone frame */
.bc-phone{width:200px;margin:0 auto;background:#111;border:2px solid rgba(255,255,255,.15);border-radius:28px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.5);}
.bc-phone-status{height:24px;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:space-between;padding:0 12px;}
.bc-phone-screen{background:#1a1a2e;min-height:140px;padding:12px;}
.bc-phone-home{height:20px;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;}
.bc-phone-home::before{content:'';width:50px;height:3px;background:rgba(255,255,255,.3);border-radius:9999px;}

/* Notification bubble */
.bc-notif-bubble{background:rgba(255,255,255,.10);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:10px 12px;}
.bc-notif-app{display:flex;align-items:center;gap:7px;margin-bottom:7px;font-size:10px;font-weight:700;color:rgba(255,255,255,.6);}
.bc-notif-app-icon{width:18px;height:18px;border-radius:5px;background:linear-gradient(135deg,#E76F51,#F4A261);display:flex;align-items:center;justify-content:center;}
.bc-notif-app-icon svg{width:10px;height:10px;}
.bc-notif-title{font-size:12px;font-weight:700;color:#fff;margin-bottom:4px;line-height:1.3;word-break:break-word;}
.bc-notif-msg{font-size:10px;color:rgba(255,255,255,.65);line-height:1.4;word-break:break-word;}

/* Tips */
.bc-tips{background:rgba(255,215,0,.05);border:1px solid rgba(255,215,0,.18);border-radius:16px;padding:18px 20px;}
.bc-tips-header{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;color:var(--go);letter-spacing:.5px;margin-bottom:12px;}
.bc-tips-header svg{width:14px;height:14px;flex-shrink:0;}
.bc-tips-list{list-style:none;display:flex;flex-direction:column;gap:8px;}
.bc-tips-list li{display:flex;align-items:flex-start;gap:8px;font-size:12px;color:var(--tm);line-height:1.5;}
.bc-tip-dot{width:5px;height:5px;border-radius:50%;background:var(--go);flex-shrink:0;margin-top:5px;}

@media(max-width:768px){
  .bc-section{padding:16px;}
  .bc-right-col{position:static;}
}
</style>

<script>
function updatePreview(){
  const t = document.querySelector('[name="title"]').value;
  const m = document.querySelector('[name="message"]').value;
  document.getElementById('previewTitle').textContent = t || 'Judul notifikasi';
  document.getElementById('previewMsg').textContent   = m || 'Isi pesan notifikasi akan muncul di sini...';
  document.getElementById('titleCount').textContent   = t.length + ' / 120';
  document.getElementById('msgCount').textContent     = m.length + ' / 500';
}
updatePreview();
</script>
@endsection
