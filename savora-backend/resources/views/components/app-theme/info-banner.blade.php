{{--
    Info Banner Component
    Usage: <x-app-theme.info-banner message="Resep ini membutuhkan persetujuan admin." />

    @props:
      message (string) - isi pesan
      icon    (string) - icon class (default info)
--}}
@props(['message', 'icon' => 'bi bi-info-circle'])

<div class="info-banner-savora">
    <i class="{{ $icon }} banner-icon"></i>
    <p class="banner-text">{{ $message }}</p>
</div>