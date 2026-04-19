@extends('admin.layout')

@section('title', 'Admin Users')

@section('content')
    <div class="section-title">User Management</div>

    <div class="panel">
        <form class="filters" method="GET" action="{{ route('admin.users') }}">
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Cari username/full name/email...">
            <select name="status">
                <option value="all" @selected($filters['status'] === 'all')>All</option>
                <option value="active" @selected($filters['status'] === 'active')>Active</option>
                <option value="banned" @selected($filters['status'] === 'banned')>Banned</option>
                <option value="premium" @selected($filters['status'] === 'premium')>Premium</option>
            </select>
            <button type="submit">Terapkan</button>
        </form>

        <table>
            <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Status</th>
                <th>Role</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <strong>{{ $user['username'] ?? '-' }}</strong><br>
                        <span class="muted">{{ $user['full_name'] ?? '-' }}</span>
                    </td>
                    <td>{{ $user['email'] ?? '-' }}</td>
                    <td>
                        @if(($user['is_banned'] ?? false) === true)
                            <span class="badge" style="color:#ff7a7a">Banned</span>
                            <div class="muted">{{ $user['banned_reason'] ?? '-' }}</div>
                        @else
                            <span class="badge" style="color:#6de881">Active</span>
                        @endif
                        @if(($user['is_premium'] ?? false) === true)
                            <span class="badge" style="color:#ffd700">Premium</span>
                        @endif
                    </td>
                    <td>{{ strtoupper((string) ($user['role'] ?? 'user')) }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($user['created_at'] ?? now())->format('d M Y H:i') }}</td>
                    <td>
                        <div class="actions">
                            <form method="POST" action="{{ route('admin.users.toggle-ban', $user['id']) }}">
                                @csrf
                                <input type="hidden" name="is_banned" value="{{ ($user['is_banned'] ?? false) ? 1 : 0 }}">
                                @if(($user['is_banned'] ?? false) === true)
                                    <button class="btn btn-green" type="submit">Unban</button>
                                @else
                                    <select name="reason_type" style="max-width:140px;">
                                        <option value="spam">Spam</option>
                                        <option value="inappropriate_content">Inappropriate</option>
                                        <option value="harassment">Harassment</option>
                                        <option value="fake_account">Fake Account</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <input name="reason" placeholder="Custom reason" style="max-width:120px;">
                                    <button class="btn btn-red" type="submit">Ban</button>
                                @endif
                            </form>

                            <form method="POST" action="{{ route('admin.users.toggle-premium', $user['id']) }}">
                                @csrf
                                <button class="btn btn-blue" type="submit">
                                    {{ ($user['is_premium'] ?? false) ? 'Remove Premium' : 'Grant Premium' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Data user kosong.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection