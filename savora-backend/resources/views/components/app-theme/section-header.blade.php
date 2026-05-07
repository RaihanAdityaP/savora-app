@props(['title', 'icon' => 'bi bi-circle', 'titleColor' => null])

<div class="section-header-savora">
    <div class="accent-bar"></div>
    <div class="icon-box">
        @if(str_starts_with(trim($icon), '<svg') || str_starts_with(trim($icon), '<path'))
            {!! $icon !!}
        @else
            <i class="{{ $icon }}"></i>
        @endif
    </div>
    <h3 class="header-title" @if($titleColor) style="color: {{ $titleColor }} !important" @endif>
        {{ $title }}
    </h3>
</div>