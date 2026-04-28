@extends('admin.layout')
@section('title','Recipe Moderation')
@section('page-title','RECIPE MODERATION')
@section('content')
@if($error)<div class="al al-err"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/></svg>{{ $error }}</div>@endif

<div class="f ac jb mb4" style="flex-wrap:wrap;gap:16px;">
  <div style="flex:1;"></div>
  <form method="GET" action="{{ route('admin.recipes') }}" class="f ac g3" style="flex-wrap:wrap;width:100%;">
    <div class="sbox" style="flex:1;min-width:0;">
      <svg viewBox="0 0 24 24" fill="none" stroke="var(--go)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search recipes..." class="inp" style="width:100%;">
    </div>
    <input type="hidden" name="status" value="{{ $filters['status'] }}">
    <button type="submit" class="btn btn-go">Search</button>
  </form>
</div>

<div class="chips mb4">
  @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','all'=>'All'] as $v=>$l)
    <a href="{{ route('admin.recipes',['status'=>$v,'search'=>$filters['search']]) }}" class="chip {{ $filters['status']===$v?'on':'' }}">{{ $l }}</a>
  @endforeach
</div>

<div class="card">
@if($recipes->isEmpty())
  <div class="empty">
    <div class="e-ico"><svg viewBox="0 0 24 24"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg></div>
    <div class="e-ttl">No Recipes Found</div>
    <div class="e-sub">Try a different filter</div>
  </div>
@else
  <table>
    <thead><tr>
      <th>Recipe</th><th>Author</th><th>Details</th><th>Status</th><th>Date</th><th class="tr">Actions</th>
    </tr></thead>
    <tbody>
    @foreach($recipes as $recipe)
    @php
      $status   = $recipe['status'] ?? 'pending';
      $profile  = $recipe['profiles'] ?? null;
      $username = $profile['username'] ?? '—';
      $avUrl    = $profile['avatar_url'] ?? null;
      $initials = strtoupper(substr($username,0,1));
      $created  = isset($recipe['created_at']) ? \Carbon\Carbon::parse($recipe['created_at'])->format('d M Y') : '—';
      $imgUrl   = $recipe['image_url'] ?? null;
      $bc       = match($status){ 'approved'=>'b-approved','rejected'=>'b-rejected',default=>'b-pending' };
    @endphp
    <tr>
      <td>
        <div class="f ac g3">
          <div class="rimg">
            @if($imgUrl)<img src="{{ $imgUrl }}" alt="">
            @else<svg viewBox="0 0 24 24"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/></svg>@endif
          </div>
          <div>
            <div class="fw6 trunc" style="max-width:220px;">{{ $recipe['title'] ?? '—' }}</div>
            @if($recipe['description'] ?? null)
            <div class="ts tc trunc" style="max-width:220px;">{{ $recipe['description'] }}</div>
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
        <div class="f ac g2" style="flex-wrap:wrap;">
          @if($recipe['cooking_time'] ?? null)
            <span class="badge" style="background:rgba(255,152,0,.1);border:1px solid rgba(255,152,0,.2);color:var(--or2);">{{ $recipe['cooking_time'] }}m</span>
          @endif
          @if($recipe['difficulty'] ?? null)
            <span class="badge" style="background:rgba(33,150,243,.1);border:1px solid rgba(33,150,243,.2);color:var(--bl2);">{{ ucfirst($recipe['difficulty']) }}</span>
          @endif
        </div>
      </td>
      <td><span class="badge {{ $bc }}">{{ ucfirst($status) }}</span></td>
      <td class="tc ts">{{ $created }}</td>
      <td class="tr">
        <div class="f ac g2" style="justify-content:flex-end;">
          <button type="button" class="btn btn-gh btn-sm" onclick="openRecipeModal({{ json_encode($recipe) }})">
            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>View
          </button>
          @if($status === 'pending')
          <form method="POST" action="{{ route('admin.recipes.moderate',$recipe['id']) }}" style="display:inline;">
            @csrf<input type="hidden" name="action" value="approved">
            <button type="submit" class="btn btn-gr btn-sm" onclick="return confirm('Approve recipe?')">
              <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Approve
            </button>
          </form>
          <button type="button" class="btn btn-re btn-sm" onclick="openRejectModal('{{ $recipe['id'] }}','{{ addslashes($recipe['title'] ?? '') }}')">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Reject
          </button>
          @endif
        </div>
      </td>
    </tr>
    @endforeach
    </tbody>
  </table>
@endif
</div>

<!-- RECIPE DETAIL MODAL -->
<div class="mbk" id="recipeModal">
  <div class="modal modal-xl">
    <div class="f ac jb mb5">
      <div class="m-ttl" id="rModalTitle">Recipe Detail</div>
      <button type="button" onclick="document.getElementById('recipeModal').classList.remove('open')" class="btn btn-gh btn-sm">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Close
      </button>
    </div>
    <div id="rImg" class="mb4"></div>
    <div id="rMeta" class="f ac g3 mb4" style="flex-wrap:wrap;"></div>
    <div id="rDesc" class="mb4"></div>
    <div id="rIngr" class="mb4"></div>
    <div id="rSteps"></div>
  </div>
</div>

<!-- REJECT MODAL -->
<div class="mbk" id="rejectModal">
  <div class="modal">
    <div class="m-head">
      <div class="m-ico" style="background:linear-gradient(135deg,var(--re),var(--re2));">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
      <div>
        <div class="m-ttl">Reject Recipe</div>
        <div class="ts tc"><span id="rejectTitle" style="color:var(--go);font-weight:700;"></span></div>
      </div>
    </div>
    <form method="POST" id="rejectForm">
      @csrf<input type="hidden" name="action" value="rejected">
      <div class="lbl">Rejection Reason</div>
      <textarea name="reason" class="ta" rows="4" placeholder="Enter reason...">Tidak memenuhi standar</textarea>
      <div class="f ac g3 mt5">
        <button type="button" class="btn btn-gh wf" onclick="document.getElementById('rejectModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-re wf">
          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Reject
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
@push('scripts')
<script>
function openRecipeModal(r){
  document.getElementById('rModalTitle').textContent=r.title||'Recipe Detail';
  document.getElementById('rImg').innerHTML=r.image_url?`<img src="${r.image_url}" style="width:100%;height:220px;object-fit:cover;border-radius:16px;border:1px solid rgba(255,255,255,.07);">`:'';
  const mB=(col,label,val)=>`<div style="background:${col}11;border:1px solid ${col}33;border-radius:10px;padding:12px 16px;text-align:center;min-width:100px;"><div style="font-size:10px;font-weight:700;letter-spacing:1px;color:${col};margin-bottom:4px;">${label}</div><div style="font-size:15px;font-weight:700;">${val}</div></div>`;
  let meta='';
  if(r.cooking_time)meta+=mB('#FF9800','COOK TIME',r.cooking_time+' min');
  if(r.servings)meta+=mB('#4CAF50','SERVINGS',r.servings);
  if(r.difficulty)meta+=mB('#2196F3','DIFFICULTY',r.difficulty);
  document.getElementById('rMeta').innerHTML=meta;
  const sec=(title,content)=>`<div style="font-size:11px;font-weight:700;letter-spacing:1.2px;color:var(--go);margin-bottom:8px;">${title}</div><div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:14px;font-size:13px;color:var(--tm);line-height:1.7;">${content}</div>`;
  document.getElementById('rDesc').innerHTML=r.description?sec('DESCRIPTION',r.description):'';
  let ing=r.ingredients;if(typeof ing==='string'){try{ing=JSON.parse(ing);}catch(e){}}
  document.getElementById('rIngr').innerHTML=ing&&ing.length?sec('INGREDIENTS',(Array.isArray(ing)?ing:[ing]).map(i=>`• ${i}`).join('<br>')):'';
  let st=r.steps;if(typeof st==='string'){try{st=JSON.parse(st);}catch(e){}}
  document.getElementById('rSteps').innerHTML=st&&st.length?sec('STEPS',(Array.isArray(st)?st:[st]).map((s,i)=>`<strong style="color:var(--tw)">${i+1}.</strong> ${s}`).join('<br><br>')):'';
  document.getElementById('recipeModal').classList.add('open');
}
function openRejectModal(id,title){
  document.getElementById('rejectTitle').textContent=title;
  document.getElementById('rejectForm').action='/admin/recipes/'+id+'/moderate';
  document.getElementById('rejectModal').classList.add('open');
}
document.querySelectorAll('.mbk').forEach(el=>el.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
@endpush