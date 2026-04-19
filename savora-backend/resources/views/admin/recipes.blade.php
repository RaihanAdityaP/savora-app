@extends('admin.layout')

@section('title', 'Admin Recipes')

@section('content')
    <div class="section-title">Recipe Moderation</div>

    <div class="panel">
        <form class="filters" method="GET" action="{{ route('admin.recipes') }}">
            <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Cari judul/description...">
            <select name="status">
                <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                <option value="approved" @selected($filters['status'] === 'approved')>Approved</option>
                <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
                <option value="all" @selected($filters['status'] === 'all')>All</option>
            </select>
            <button type="submit">Load</button>
        </form>

        <table>
            <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($recipes as $recipe)
                <tr>
                    <td>{{ $recipe['title'] ?? '-' }}</td>
                    <td>{{ $recipe['profiles']['username'] ?? '-' }}</td>
                    <td>
                        @php($st = (string) ($recipe['status'] ?? 'unknown'))
                        <span class="badge" style="color:{{ $st === 'approved' ? '#6de881' : ($st === 'rejected' ? '#ff7a7a' : '#ffcc66') }}">{{ strtoupper($st) }}</span>
                    </td>
                    <td>{{ \Illuminate\Support\Carbon::parse($recipe['created_at'] ?? now())->format('d M Y H:i') }}</td>
                    <td>
                        <div class="actions">
                            <button class="btn btn-blue" type="button" onclick='openPreview(@json($recipe))'>Preview</button>
                            <form method="POST" action="{{ route('admin.recipes.moderate', $recipe['id']) }}">
                                @csrf
                                <input type="hidden" name="action" value="approved">
                                <button class="btn btn-green" type="submit">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.recipes.moderate', $recipe['id']) }}">
                                @csrf
                                <input type="hidden" name="action" value="rejected">
                                <input type="text" name="reason" placeholder="Reason" style="max-width:120px;">
                                <button class="btn btn-orange" type="submit">Reject</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Data resep kosong.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <dialog id="previewDialog" style="max-width:760px; width:90%; border:none; border-radius:16px; background:#141414; color:#fff; padding:0;">
        <div style="padding:18px; border-bottom:1px solid rgba(255,255,255,.1); display:flex; justify-content:space-between; align-items:center;">
            <strong id="p-title">Preview Recipe</strong>
            <button class="btn btn-red" onclick="document.getElementById('previewDialog').close()">Close</button>
        </div>
        <div style="padding:18px; max-height:70vh; overflow:auto;">
            <div class="muted" id="p-author"></div>
            <p id="p-desc" style="white-space:pre-wrap;"></p>
            <h4>Ingredients</h4>
            <pre id="p-ing" style="white-space:pre-wrap; background:#101010; padding:10px; border-radius:10px;"></pre>
            <h4>Steps</h4>
            <pre id="p-steps" style="white-space:pre-wrap; background:#101010; padding:10px; border-radius:10px;"></pre>
        </div>
    </dialog>
@endsection

@section('scripts')
<script>
    function openPreview(recipe) {
        const dialog = document.getElementById('previewDialog');
        document.getElementById('p-title').textContent = recipe.title || '-';
        document.getElementById('p-author').textContent = 'by ' + ((recipe.profiles && recipe.profiles.username) || '-');
        document.getElementById('p-desc').textContent = recipe.description || '-';
        const ingredients = Array.isArray(recipe.ingredients) ? recipe.ingredients.join('\n') : JSON.stringify(recipe.ingredients || '-', null, 2);
        const steps = Array.isArray(recipe.steps) ? recipe.steps.join('\n') : JSON.stringify(recipe.steps || '-', null, 2);
        document.getElementById('p-ing').textContent = ingredients;
        document.getElementById('p-steps').textContent = steps;
        dialog.showModal();
    }
</script>
@endsection