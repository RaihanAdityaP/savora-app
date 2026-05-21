@extends('admin.layout')

@section('title', 'Broadcast Notification')

@section('content')
<div class="hdr">
  <div>
    <h1>Broadcast Notification</h1>
    <p>Kirim notifikasi manual ke satu user atau semua user aktif.</p>
  </div>
</div>

@if(session('status'))
  <div class="ok">{{ session('status') }}</div>
@endif
@if(session('error') || $error)
  <div class="err">{{ session('error') ?? $error }}</div>
@endif

<div class="card">
  <form method="POST" action="{{ route('admin.broadcast.send') }}" class="grid" style="gap:18px;max-width:760px;">
    @csrf

    <label class="lbl">Audience</label>
    <select name="audience" class="inp" onchange="document.getElementById('targetUser').style.display=this.value==='user'?'block':'none'">
      <option value="all" @selected(old('audience') === 'all')>Semua user aktif</option>
      <option value="user" @selected(old('audience') === 'user')>User tertentu</option>
    </select>

    <div id="targetUser" style="display:{{ old('audience') === 'user' ? 'block' : 'none' }}">
      <label class="lbl">Target User</label>
      <select name="user_id" class="inp">
        <option value="">Pilih user...</option>
        @foreach($users as $user)
          <option value="{{ $user['id'] }}" @selected(old('user_id') === $user['id'])>
            {{ $user['username'] ?? $user['full_name'] ?? $user['id'] }}
          </option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="lbl">Title</label>
      <input name="title" class="inp" value="{{ old('title') }}" maxlength="120" required placeholder="Contoh: Event Baru di Savora">
    </div>

    <div>
      <label class="lbl">Message</label>
      <textarea name="message" class="inp" rows="4" maxlength="500" required placeholder="Tulis pesan notifikasi...">{{ old('message') }}</textarea>
    </div>

    <div class="grid" style="grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label class="lbl">Tap Action</label>
        <select name="route" class="inp">
          <option value="home" @selected(old('route') === 'home')>Home</option>
          <option value="recipe" @selected(old('route') === 'recipe')>Recipe detail</option>
          <option value="profile" @selected(old('route') === 'profile')>Profile</option>
        </select>
      </div>
      <div>
        <label class="lbl">Related ID</label>
        <input name="related_entity_id" class="inp" value="{{ old('related_entity_id') }}" placeholder="UUID recipe/profile jika perlu">
      </div>
    </div>

    <button class="btn" type="submit">Kirim Broadcast</button>
  </form>
</div>
@endsection
