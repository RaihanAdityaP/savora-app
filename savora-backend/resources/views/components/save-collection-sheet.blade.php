@props([
    'recipeId',
    'boards' => [],
    'savedBoardIds' => [],
])

@php
    $isEnglish = session('user_language', 'en') === 'en';
@endphp

<template x-teleport="body">
    <div
        x-show="openBoardSelector"
        x-transition.opacity
        class="fixed inset-0 z-[60]"
        style="display: none;"
    >
        <div class="absolute inset-0 bg-black/40" @click="openBoardSelector = false"></div>
        <div class="absolute inset-x-0 bottom-0 w-full md:max-w-xl md:mx-auto rounded-t-3xl p-6 max-h-[78vh] overflow-y-auto shadow-2xl bg-white">
            <div class="w-10 h-1 rounded-full mx-auto mb-5 bg-gray-300"></div>

            <div class="flex items-center gap-3 mb-5">
                <div class="shrink-0 p-2.5 rounded-xl text-white"
                     style="background: linear-gradient(135deg, var(--color-primary-coral), var(--color-primary-orange));">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                </div>
                <h3 class="text-[22px] font-bold leading-tight flex-1" style="color: var(--color-text-primary);">{{ $isEnglish ? 'Save to Collection' : 'Simpan ke Koleksi' }}</h3>
            </div>

            <a href="{{ route('app.favorites') }}"
               class="flex items-center gap-3 w-full px-4 py-4 rounded-2xl mb-4 text-white font-bold shadow-lg transition-opacity hover:opacity-95"
               style="background: linear-gradient(135deg, var(--color-primary-coral), var(--color-primary-orange)); box-shadow: 0 4px 14px rgba(231, 111, 81, 0.35);">
                <span class="shrink-0 w-9 h-9 rounded-[10px] grid place-items-center" style="background: rgba(255,255,255,0.3);">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                </span>
                <span class="flex-1 text-left text-base">{{ $isEnglish ? 'Create New Collection' : 'Buat Koleksi Baru' }}</span>
                <svg class="w-4 h-4 text-white shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            @if(empty($boards))
                <div class="rounded-2xl p-8 text-center" style="background: #F9FAFB;">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <p class="text-base font-semibold text-gray-600">{{ $isEnglish ? 'No collections yet' : 'Belum ada koleksi' }}</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($boards as $board)
                        <form action="{{ route('app.favorites.save') }}" method="POST">
                            @csrf
                            <input type="hidden" name="recipe_id" value="{{ $recipeId }}">
                            <input type="hidden" name="board_id" value="{{ $board['id'] }}">
                            <button type="submit"
                                    class="w-full text-left rounded-2xl border-[1.5px] bg-white px-4 py-4 shadow-sm transition-shadow hover:shadow-md flex items-center gap-3"
                                    style="border-color: rgba(231, 111, 81, 0.2); box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                <div class="shrink-0 p-2.5 rounded-[10px]"
                                     style="background: linear-gradient(135deg, rgba(231,111,81,0.2), rgba(244,162,97,0.1));">
                                    <svg class="w-5 h-5" style="color: var(--color-primary-coral);" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-[15px] leading-snug" style="color: var(--color-text-primary);">{{ $board['name'] }}</p>
                                    @if(!empty($board['description']))
                                        <p class="text-xs mt-0.5 truncate text-gray-600">{{ $board['description'] }}</p>
                                    @endif
                                    @if(collect($savedBoardIds ?? [])->contains(fn ($id) => (string) $id === (string) ($board['id'] ?? '')))
                                        <p class="text-xs mt-1 font-semibold" style="color: #10B981;">{{ $isEnglish ? 'Already saved in this collection' : 'Sudah tersimpan di koleksi ini' }}</p>
                                    @endif
                                </div>
                                <svg class="w-3.5 h-3.5 shrink-0" style="color: var(--color-primary-coral);" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</template>
