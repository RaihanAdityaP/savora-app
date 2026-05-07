{{--
    Empty State Component
    Usage:
      <x-app-theme.empty-state
          icon="bi bi-inbox"
          title="Belum ada resep"
          subtitle="Tambahkan resep pertamamu!" />

    @props:
      icon     (string) - icon class Bootstrap Icons / Heroicons
      title    (string) - pesan utama
      subtitle (string) - pesan tambahan (opsional)
--}}
@props(['icon', 'title', 'subtitle' => null])

<div class="empty-state-savora">
    <div class="empty-icon">
        <i class="{{ $icon }}"></i>
    </div>
    <p class="empty-title">{{ $title }}</p>
    @if($subtitle)
        <p class="empty-subtitle">{{ $subtitle }}</p>
    @endif
    {{ $slot }}
</div>