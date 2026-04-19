@extends('admin.layout')

@section('title', 'Admin Logs')

@section('content')
    <div class="section-title">Activity Logs</div>

    <div class="panel">
        <form class="filters" method="GET" action="{{ route('admin.logs') }}">
            <select name="action">
                <option value="all" @selected($filters['action'] === 'all')>All</option>
                <option value="ban_user" @selected($filters['action'] === 'ban_user')>Ban</option>
                <option value="unban_user" @selected($filters['action'] === 'unban_user')>Unban</option>
                <option value="moderate_recipe" @selected($filters['action'] === 'moderate_recipe')>Moderate</option>
                <option value="delete_recipe" @selected($filters['action'] === 'delete_recipe')>Delete Recipe</option>
                <option value="delete_comment" @selected($filters['action'] === 'delete_comment')>Delete Comment</option>
                @foreach($availableActions as $act)
                    @if(!in_array($act, ['ban_user','unban_user','moderate_recipe','delete_recipe','delete_comment'], true))
                        <option value="{{ $act }}" @selected($filters['action'] === $act)>{{ strtoupper(str_replace('_', ' ', $act)) }}</option>
                    @endif
                @endforeach
            </select>
            <button type="submit">Filter</button>
        </form>

        <table>
            <thead>
            <tr>
                <th>Action</th>
                <th>User</th>
                <th>Details</th>
                <th>Time</th>
            </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td><span class="badge">{{ strtoupper(str_replace('_', ' ', (string) ($log['action'] ?? '-'))) }}</span></td>
                    <td>{{ $log['profiles']['username'] ?? '-' }}</td>
                    <td>
                        @php($details = $log['details'] ?? $log['metadata'] ?? [])
                        @if(is_array($details) && !empty($details))
                            <pre style="margin:0; white-space:pre-wrap; background:#101010; border-radius:8px; padding:8px;">{{ json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ \Illuminate\Support\Carbon::parse($log['created_at'] ?? now())->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada activity log.</td></tr>
            @endforelse
            </tbody>
        </table>

        @if($paginator->lastPage() > 1)
            <div class="pagination">
                @if($paginator->onFirstPage())
                    <span>Prev</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}">Prev</a>
                @endif

                <span>Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

                @if($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}">Next</a>
                @else
                    <span>Next</span>
                @endif
            </div>
        @endif
    </div>
@endsection