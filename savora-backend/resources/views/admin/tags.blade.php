@extends('admin.layout')
@section('title','Tag Moderation')
@section('page-title','TAG MODERATION')
@section('content')
@if($error)<div class="al al-err"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>{{ $error }}</div>@endif

<div class="f ac jb mb4" style="flex-wrap:wrap;gap:16px;">
  <div style="flex:1;"></div>
  <form method="GET" action="{{ route('admin.tags') }}" class="f ac g3" style="flex-wrap:wrap;width:100%;">
    <div class="sbox" style="flex:1;min-width:0;">
      <svg viewBox="0 0 24 24" fill="none" stroke="var(--go)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search tags..." class="inp" style="width:100%;">
    </div>
    <input type="hidden" name="status" value="{{ $filters['status'] }}">
    <button type="submit" class="btn btn-go">Search</button>
  </form>
</div>

<div class="chips mb4">
  @foreach(['pending'=>'Pending','approved'=>'Approved','all'=>'All'] as $v=>$l)
    <a href="{{ route('admin.tags',['status'=>$v,'search'=>$filters['search']]) }}"
       class="chip {{ $filters['status']===$v?'on':'' }}">
      {{ $l }}
      @if($v==='pending' && $pendingCount > 0)
        <span style="margin-left:6px;background:var(--re);color:#fff;border-radius:20px;padding:1px 7px;font-size:10px;font-weight:700;">{{ $pendingCount }}</span>
      @endif
    </a>
  @endforeach
</div>

<div class="card">
@if($tags->isEmpty())
  <div class="empty">
    <div class="e-ico">
      <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
    </div>
    <div class="e-ttl">No Tags Found</div>
    <div class="e-sub">Try a different filter or search term</div>
  </div>
@else
  <table>
    <thead><tr>
      <th>Tag</th><th>Created By</th><th>Usage</th><th>Status</th><th>Date</th><th class="tr">Actions</th>
    </tr></thead>
    <tbody>
    @foreach($tags as $tag)
    @php
      $isApproved = (bool)($tag['is_approved'] ?? false);
      $profile    = $tag['profiles'] ?? null;
      $username   = $profile['username'] ?? '—';
      $avUrl      = $profile['avatar_url'] ?? null;
      $initials   = strtoupper(substr($username, 0, 1));
      $created    = isset($tag['created_at']) ? \Carbon\Carbon::parse($tag['created_at'])->format('d M Y') : '—';
      $usage      = (int)($tag['usage_count'] ?? 0);
    @endphp
    <tr>
      <td>
        <div class="f ac g3">
          <div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,rgba(255,215,0,.15),rgba(255,165,0,.08));border:1px solid rgba(255,215,0,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--go)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
          </div>
          <div>
            <div class="fw6">#{{ $tag['name'] ?? '—' }}</div>
            @if($tag['slug'] ?? null)
            <div class="ts tc">{{ $tag['slug'] }}</div>
            @endif
          </div>
        </div>
      </td>
      <td>
        <div class="f ac g2">
          <div class="ava" style="width:28px;height:28px;font-size:11px;">
            @if($avUrl)<img src="{{ $avUrl }}" alt="">@else{{ $initials }}@endif
          </div>
          <span class="ts">{{ $username }}</span>
        </div>
      </td>
      <td>
        <span class="badge" style="background:rgba(33,150,243,.1);border:1px solid rgba(33,150,243,.2);color:var(--bl2);">
          {{ $usage }} resep
        </span>
      </td>
      <td>
        @if($isApproved)
          <span class="badge b-approved">Approved</span>
        @else
          <span class="badge b-pending">Pending</span>
        @endif
      </td>
      <td class="tc ts">{{ $created }}</td>
      <td class="tr">
        <div class="f ac g2" style="justify-content:flex-end;">
          @if(!$isApproved)
            {{-- Approve --}}
            <form method="POST" action="{{ route('admin.tags.moderate', $tag['id']) }}" style="display:inline;">
              @csrf
              <input type="hidden" name="action" value="approved">
              <button type="submit" class="btn btn-gr btn-sm" onclick="return confirm('Approve tag #{{ addslashes($tag['name'] ?? '') }}?')">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Approve
              </button>
            </form>
            {{-- Reject (delete) --}}
            <form method="POST" action="{{ route('admin.tags.moderate', $tag['id']) }}" style="display:inline;">
              @csrf
              <input type="hidden" name="action" value="rejected">
              <button type="submit" class="btn btn-re btn-sm" onclick="return confirm('Reject and delete tag #{{ addslashes($tag['name'] ?? '') }}?')">
                <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Reject
              </button>
            </form>
          @else
            {{-- Force delete approved tag --}}
            <form method="POST" action="{{ route('admin.tags.destroy', $tag['id']) }}" style="display:inline;">
              @csrf
              <button type="submit" class="btn btn-re btn-sm" onclick="return confirm('Delete approved tag #{{ addslashes($tag['name'] ?? '') }}? This will detach it from all recipes.')">
                <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>Delete
              </button>
            </form>
          @endif
        </div>
      </td>
    </tr>
    @endforeach
    </tbody>
  </table>
@endif
</div>
@endsection