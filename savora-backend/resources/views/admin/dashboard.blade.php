@extends('admin.layout')
@section('title','Dashboard')
@section('page-title','PLATFORM OVERVIEW')
@section('content')
@if($error)<div class="al al-err"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>{{ $error }}</div>@endif

<div class="sg">
@php
$cards = [
  ['total_users','Total Users','linear-gradient(135deg,#4CAF50,#66BB6A)','rgba(76,175,80,.3)','rgba(76,175,80,.1)','#66BB6A','<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
  ['banned_users','Banned Users','linear-gradient(135deg,#F44336,#E57373)','rgba(244,67,54,.3)','rgba(244,67,54,.1)','#E57373','<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>'],
  ['pending_recipes','Pending Recipes','linear-gradient(135deg,#FF9800,#FFB74D)','rgba(255,152,0,.3)','rgba(255,152,0,.1)','#FFB74D','<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
  ['total_recipes','Total Recipes','linear-gradient(135deg,#9C27B0,#BA68C8)','rgba(156,39,176,.3)','rgba(156,39,176,.1)','#BA68C8','<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>'],
];
@endphp
@foreach($cards as [$key,$label,$grad,$glow,$bgc,$textc,$ico])
<div class="sc" style="box-shadow:0 0 30px {{ $bgc }};">
  <div class="sc-ico" style="background:{{ $grad }};box-shadow:0 4px 16px {{ $glow }};">
    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ico !!}</svg>
  </div>
  <div class="sc-val" style="background:{{ $grad }};-webkit-background-clip:text;-webkit-text-fill-color:transparent;">{{ number_format($stats[$key]) }}</div>
  <div class="sc-lbl">{{ $label }}</div>
</div>
@endforeach
</div>

<div class="sh mb4"><div class="sh-bar"></div><span class="sh-ttl">MANAGEMENT</span></div>
<div class="mg">
@php
$menus = [
  [route('admin.users'),  'linear-gradient(135deg,#4CAF50,#66BB6A)', 'rgba(76,175,80,.25)',  'User Management',   $stats['total_users'].' registered users',   '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
  [route('admin.recipes'),'linear-gradient(135deg,#FF9800,#FFB74D)', 'rgba(255,152,0,.25)',  'Recipe Moderation', $stats['pending_recipes'].' awaiting approval', '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>'],
  [route('admin.tags'),   'linear-gradient(135deg,#FFD700,#FFA500)', 'rgba(255,215,0,.25)',  'Tag Moderation',    ($stats['pending_tags'] ?? 0).' awaiting approval', '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>'],
  [route('admin.logs'),   'linear-gradient(135deg,#2196F3,#64B5F6)', 'rgba(33,150,243,.25)', 'Activity Logs',     'Monitor all activities',                     '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
];
@endphp
@foreach($menus as [$href,$grad,$glow,$title,$sub,$ico])
<a href="{{ $href }}" class="mc" style="border-color:{{ $glow }};box-shadow:0 8px 30px {{ $glow }};">
  <div class="mc-ico" style="background:{{ $grad }};box-shadow:0 6px 20px {{ $glow }};">
    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $ico !!}</svg>
  </div>
  <div class="mc-t">
    <div class="mc-title">{{ $title }}</div>
    <div class="mc-sub">{{ $sub }}</div>
  </div>
  <div class="mc-arr">
    <svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
  </div>
</a>
@endforeach
</div>
@endsection