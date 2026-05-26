@extends('admin.layout')
@section('title','Presensi')
@section('page-title','PRESENSI')
@section('content')
@php
  $typeLabels = ['murid' => 'Murid', 'guru' => 'Guru', 'tamu_undangan' => 'Tamu Undangan'];
  $majorLabels = ['pplg' => 'PPLG', 'tjkt' => 'TJKT', 'dkv' => 'DKV', 'lk' => 'LK', 'ps' => 'PS'];
@endphp

<div class="sg">
  <div class="sc">
    <div class="sc-ico" style="background:linear-gradient(135deg,var(--go),var(--go2));"><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
    <div class="sc-val">{{ $stats['total'] }}</div>
    <div class="sc-lbl">Total Presensi</div>
  </div>
  <div class="sc">
    <div class="sc-ico" style="background:linear-gradient(135deg,var(--bl),var(--bl2));"><svg viewBox="0 0 24 24"><path d="M22 10L12 4 2 10l10 6 10-6z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
    <div class="sc-val">{{ $stats['murid'] }}</div>
    <div class="sc-lbl">Murid</div>
  </div>
  <div class="sc">
    <div class="sc-ico" style="background:linear-gradient(135deg,var(--gr),var(--gr2));"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
    <div class="sc-val">{{ $stats['guru'] }}</div>
    <div class="sc-lbl">Guru</div>
  </div>
  <div class="sc">
    <div class="sc-ico" style="background:linear-gradient(135deg,var(--pu),var(--pu2));"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></div>
    <div class="sc-val">{{ $stats['tamu_undangan'] }}</div>
    <div class="sc-lbl">Tamu Undangan</div>
  </div>
</div>

<div class="card">
  <div class="card-hd">
    <div class="sh" style="margin-bottom:0;">
      <div class="sh-bar"></div>
      <div class="sh-ttl">DATA PRESENSI</div>
    </div>
    <span class="mla ts tc">{{ count($attendances) }} data terbaru</span>
  </div>

  @if(empty($attendances))
    <div class="empty">
      <div class="e-ico"><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
      <div class="e-ttl">Belum ada presensi</div>
      <div class="e-sub">Data akan muncul setelah user mengisi form /kehadiran</div>
    </div>
  @else
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Nama</th>
            <th>Nomor</th>
            <th>Asal</th>
            <th>Jurusan</th>
            <th>Kesan</th>
            <th>Saran / Kritik</th>
            <th>Waktu</th>
            <th class="tr">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach($attendances as $attendance)
            <tr>
              <td class="fw7">{{ $attendance['name'] ?? '-' }}</td>
              <td class="tm">{{ $attendance['contact_number'] ?? '-' }}</td>
              <td><span class="badge b-active">{{ $typeLabels[$attendance['attendee_type'] ?? ''] ?? '-' }}</span></td>
              <td class="tm">{{ $majorLabels[$attendance['major'] ?? ''] ?? '-' }}</td>
              <td class="tm" style="max-width:200px;white-space:normal;min-width:120px;">{{ $attendance['impression'] ?? '-' }}</td>
              <td class="tm" style="max-width:200px;white-space:normal;min-width:120px;">{{ $attendance['feedback'] ?? '-' }}</td>
              <td class="tc" style="white-space:nowrap;">
                @if(!empty($attendance['created_at']))
                  {{ \Carbon\Carbon::parse($attendance['created_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}
                @else
                  -
                @endif
              </td>
              <td class="tr">
                <form method="POST" action="{{ route('admin.attendance.delete', $attendance['id']) }}" style="display:inline;">
                  @csrf
                  <button type="button" class="btn btn-sm btn-re" onclick="if(confirm('Hapus data presensi ini?')) this.closest('form').submit()">
                    <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                    Hapus
                  </button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection