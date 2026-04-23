@extends('admin.layout')
@section('title','Users')
@section('page-title','USER MANAGEMENT')
@section('content')
@if($error)<div class="al al-err"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>{{ $error }}</div>@endif

<div class="f ac jb mb4" style="flex-wrap:wrap;gap:16px;">
  <div class="sh" style="margin-bottom:0;flex:1;"><div class="sh-bar"></div><span class="sh-ttl">USER MANAGEMENT</span></div>
  <form method="GET" action="{{ route('admin.users') }}" class="f ac g3" style="flex-wrap:wrap;">
    <div class="sbox">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search username..." class="inp">
    </div>
    <div class="ssel">
      <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
      <select name="status" class="sel" onchange="this.form.submit()">
        <option value="all"     {{ $filters['status']==='all'     ?'selected':'' }}>All Users</option>
        <option value="active"  {{ $filters['status']==='active'  ?'selected':'' }}>Active</option>
        <option value="banned"  {{ $filters['status']==='banned'  ?'selected':'' }}>Banned</option>
        <option value="premium" {{ $filters['status']==='premium' ?'selected':'' }}>Premium</option>
      </select>
    </div>
    <button type="submit" class="btn btn-go">Search</button>
  </form>
</div>

<div class="chips mb4">
  @foreach(['all'=>'All','active'=>'Active','banned'=>'Banned','premium'=>'Premium'] as $v=>$l)
    <a href="{{ route('admin.users',array_merge(request()->query(),['status'=>$v])) }}"
       class="chip {{ $filters['status']===$v?'on':'' }}">{{ $l }}@if($v==='all') ({{ count($users) }})@endif</a>
  @endforeach
</div>

<div class="card">
@if($users->isEmpty())
  <div class="empty">
    <div class="e-ico"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
    <div class="e-ttl">No Users Found</div>
    <div class="e-sub">Try adjusting your search or filters</div>
  </div>
@else
  <table>
    <thead><tr>
      <th>User</th><th>Role</th><th>Status</th><th>Joined</th><th class="tr">Actions</th>
    </tr></thead>
    <tbody>
    @foreach($users as $user)
    @php
      $isBanned  = (bool)($user['is_banned'] ?? false);
      $isAdmin   = ($user['role'] ?? '') === 'admin';
      $isPremium = (bool)($user['is_premium'] ?? false);
      $initials  = strtoupper(substr($user['username'] ?? 'U', 0, 1));
      $avUrl     = $user['avatar_url'] ?? null;
      $created   = isset($user['created_at']) ? \Carbon\Carbon::parse($user['created_at'])->format('d M Y') : '—';
    @endphp
    <tr>
      <td>
        <div class="f ac g3">
          <div class="ava" style="border-color:{{ $isAdmin ? 'var(--go)' : ($isPremium ? 'var(--pu)' : 'var(--bd)') }};">
            @if($avUrl)<img src="{{ $avUrl }}" alt="">@else{{ $initials }}@endif
          </div>
          <div>
            <div class="f ac g2 mb2">
              <span class="fw6">{{ $user['username'] ?? '—' }}</span>
              @if($isAdmin)<span class="badge b-admin">Admin</span>@endif
              @if($isPremium)<span class="badge b-premium">Premium</span>@endif
            </div>
            <div class="ts tc">{{ $user['full_name'] ?? '' }}</div>
            @if($isBanned && ($user['banned_reason'] ?? null))
            <div class="ts" style="color:var(--re2);margin-top:2px;">{{ $user['banned_reason'] }}</div>
            @endif
          </div>
        </div>
      </td>
      <td>
        @if($isAdmin)<span class="badge b-admin">Admin</span>
        @else<span class="badge" style="background:rgba(255,255,255,.05);border:1px solid var(--bd);color:var(--tm);">User</span>@endif
      </td>
      <td>
        @if($isBanned)<span class="badge b-banned">Banned</span>
        @else<span class="badge b-active">Active</span>@endif
      </td>
      <td class="tc ts">{{ $created }}</td>
      <td class="tr">
        @if(!$isAdmin)
        <div class="f ac g2" style="justify-content:flex-end;">
          @if($isBanned)
            <form method="POST" action="{{ route('admin.users.toggle-ban', $user['id']) }}">
              @csrf<input type="hidden" name="is_banned" value="1">
              <button type="submit" class="btn btn-gr btn-sm" onclick="return confirm('Unban {{ addslashes($user['username'] ?? '') }}?')">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Unban
              </button>
            </form>
          @else
            <button type="button" class="btn btn-re btn-sm" onclick="openBanModal('{{ $user['id'] }}','{{ addslashes($user['username'] ?? '') }}')">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Ban
            </button>
          @endif
          <form method="POST" action="{{ route('admin.users.toggle-premium', $user['id']) }}">
            @csrf
            <button type="submit" class="btn btn-pu btn-sm" onclick="return confirm('{{ $isPremium ? 'Remove premium from' : 'Grant premium to' }} {{ addslashes($user['username'] ?? '') }}?')">
              <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              {{ $isPremium ? 'Remove' : 'Upgrade' }}
            </button>
          </form>
        </div>
        @else<span class="tc ts">—</span>@endif
      </td>
    </tr>
    @endforeach
    </tbody>
  </table>
@endif
</div>

<!-- BAN MODAL -->
<div class="mbk" id="banModal">
  <div class="modal">
    <div class="m-head">
      <div class="m-ico" style="background:linear-gradient(135deg,var(--re),var(--re2));">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      </div>
      <div>
        <div class="m-ttl">Ban User</div>
        <div class="ts tc">Banning: <span id="banUsername" style="color:var(--go);font-weight:700;"></span></div>
      </div>
    </div>
    <form method="POST" id="banForm">
      @csrf<input type="hidden" name="is_banned" value="0">
      <div class="lbl">Select reason</div>
      <div class="rchips">
        @foreach(['spam'=>'Spam','inappropriate_content'=>'Inappropriate Content','harassment'=>'Harassment','fake_account'=>'Fake Account','other'=>'Other'] as $v=>$l)
        <label class="rchip"><input type="radio" name="reason_type" value="{{ $v }}" {{ $v==='spam'?'checked':'' }} onchange="document.getElementById('customWrap').style.display=this.value==='other'?'block':'none'">{{ $l }}</label>
        @endforeach
      </div>
      <div id="customWrap" style="display:none;">
        <div class="lbl">Custom reason</div>
        <textarea name="reason" class="ta" rows="3" placeholder="Enter reason..."></textarea>
      </div>
      <div class="f ac g3 mt5">
        <button type="button" class="btn btn-gh wf" onclick="document.getElementById('banModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-re wf">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Ban User
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
@push('scripts')
<script>
function openBanModal(id,name){
  document.getElementById('banUsername').textContent=name;
  document.getElementById('banForm').action='/admin/users/'+id+'/toggle-ban';
  document.getElementById('banModal').classList.add('open');
}
document.getElementById('banModal').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');});
</script>
@endpush