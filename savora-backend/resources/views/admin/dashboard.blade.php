@extends('admin.layout')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="section-title">Management</div>

    <div class="panel">
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
            <a href="{{ route('admin.users') }}" style="text-decoration:none;color:#fff;">
                <div class="panel" style="margin:0;border-color:rgba(76,175,80,.35)">
                    <h3 style="margin:0 0 8px;">User Management</h3>
                    <div class="muted">Kelola user, ban/unban, premium toggle.</div>
                </div>
            </a>
            <a href="{{ route('admin.recipes') }}" style="text-decoration:none;color:#fff;">
                <div class="panel" style="margin:0;border-color:rgba(255,152,0,.35)">
                    <h3 style="margin:0 0 8px;">Recipe Moderation</h3>
                    <div class="muted">Review recipe + approve/reject dengan alasan.</div>
                </div>
            </a>
            <a href="{{ route('admin.logs') }}" style="text-decoration:none;color:#fff;">
                <div class="panel" style="margin:0;border-color:rgba(33,150,243,.35)">
                    <h3 style="margin:0 0 8px;">Activity Logs</h3>
                    <div class="muted">Pantau aktivitas dengan filter action + pagination.</div>
                </div>
            </a>
        </div>
    </div>
@endsection