@extends('admin.layout')
@section('title','Activity Logs')
@section('page-title','ACTIVITY LOGS')
@section('content')
@if($error)<div class="al al-err"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>{{ $error }}</div>@endif

<div class="f ac jb mb4" style="flex-wrap:wrap;gap:16px;">
  <div style="flex:1;"></div>
  <span class="ts tc">{{ $paginator->total() }} total entries</span>
</div>

<div class="chips mb5">
  <a href="{{ route('admin.logs',['action'=>'all']) }}" class="chip {{ $filters['action']==='all'?'on':'' }}">All</a>
  @php
  $acts=['ban_user'=>'Ban User','unban_user'=>'Unban User','moderate_recipe'=>'Moderate','delete_recipe'=>'Delete Recipe','delete_comment'=>'Delete Comment'];
  $all=array_unique(array_merge(array_keys($acts),$availableActions));
  @endphp
  @foreach($all as $a)
    <a href="{{ route('admin.logs',['action'=>$a]) }}" class="chip {{ $filters['action']===$a?'on':'' }}">{{ $acts[$a] ?? str_replace('_',' ',ucfirst($a)) }}</a>
  @endforeach
</div>

@if($logs->isEmpty())
<div class="card">
  <div class="empty">
    <div class="e-ico"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
    <div class="e-ttl">No Activities Found</div>
    <div class="e-sub">Activity logs will appear here</div>
  </div>
</div>
@else
@foreach($logs as $log)
@php
  $action   = $log['action'] ?? 'unknown';
  $username = $log['profiles']['username'] ?? 'Unknown User';
  $details  = $log['details'] ?? null;
  if(is_string($details)) $details = json_decode($details, true);
  $ago      = isset($log['created_at']) ? \Carbon\Carbon::parse($log['created_at'])->diffForHumans() : '—';
  $full     = isset($log['created_at']) ? \Carbon\Carbon::parse($log['created_at'])->format('d M Y, H:i') : '—';
  [$grad,$glow,$lico] = match($action){
    'ban_user','delete_recipe','delete_comment' =>
      ['linear-gradient(135deg,#F44336,#E57373)','rgba(244,67,54,.3)','<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>'],
    'unban_user' =>
      ['linear-gradient(135deg,#4CAF50,#66BB6A)','rgba(76,175,80,.3)','<polyline points="20 6 9 17 4 12"/>'],
    'moderate_recipe' =>
      ['linear-gradient(135deg,#FF9800,#FFB74D)','rgba(255,152,0,.3)','<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
    default =>
      ['linear-gradient(135deg,#2196F3,#64B5F6)','rgba(33,150,243,.3)','<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
  };
  $label = ['ban_user'=>'Ban User','unban_user'=>'Unban User','moderate_recipe'=>'Moderate Recipe','delete_recipe'=>'Delete Recipe','delete_comment'=>'Delete Comment'][$action] ?? strtoupper(str_replace('_',' ',$action));
@endphp
<div class="lc" style="border-color:{{ str_replace('.3','.15',$glow) }};">
  <div class="l-ico" style="background:{{ $grad }};box-shadow:0 4px 16px {{ $glow }};">
    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $lico !!}</svg>
  </div>
  <div style="flex:1;min-width:0;">
    <div class="fw7 mb2" style="font-size:15px;font-family:'Syne',sans-serif;">{{ $label }}</div>
    <div class="f ac g2 mb3 ts">
      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="var(--td)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
      <span class="tc">{{ $username }}</span>
    </div>
    @if(!empty($details))
    <div style="background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.06);border-radius:10px;padding:12px;margin-bottom:10px;">
      @foreach(['recipe_title'=>'Recipe','username'=>'Target','status'=>'Status','action'=>'Action'] as $dk=>$dl)
        @if(isset($details[$dk]))
        <div class="f g3 ts mb2">
          <span class="tc fw6" style="width:54px;flex-shrink:0;">{{ $dl }}:</span>
          <span class="tm">{{ is_string($details[$dk]) ? strtoupper($details[$dk]) : $details[$dk] }}</span>
        </div>
        @endif
      @endforeach
    </div>
    @endif
    <div class="f ac g2 ts">
      <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="var(--td)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <span class="tc" title="{{ $full }}">{{ $ago }}</span>
    </div>
  </div>
</div>
@endforeach

@if($paginator->hasPages())
<div class="card mt4">
  <div class="pager">
    <a href="{{ $paginator->previousPageUrl() ?? '#' }}" class="pg-a {{ $paginator->onFirstPage()?'dis':'' }}">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    @foreach($paginator->getUrlRange(max(1,$paginator->currentPage()-2),min($paginator->lastPage(),$paginator->currentPage()+2)) as $page=>$url)
      <a href="{{ $url }}" class="pg-a {{ $page===$paginator->currentPage()?'on':'' }}">{{ $page }}</a>
    @endforeach
    <a href="{{ $paginator->nextPageUrl() ?? '#' }}" class="pg-a {{ !$paginator->hasMorePages()?'dis':'' }}">
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    <span class="mla ts tc">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
  </div>
</div>
@endif
@endif
@endsection