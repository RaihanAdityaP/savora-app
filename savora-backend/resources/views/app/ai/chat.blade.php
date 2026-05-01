<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chef AI — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-accent { background: linear-gradient(135deg, #E76F51, #F4A261); }
        .gradient-teal   { background: linear-gradient(135deg, #2A9D8F, #3DB9A9); }
    </style>
</head>
<body class="bg-[#F5F7FA] text-gray-900">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="p-3 gradient-accent rounded-2xl shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Chef AI</h1>
                    <p class="text-gray-500 text-sm">Asisten memasak berbasis AI</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('app.ai.settings') }}"
                   class="p-2.5 bg-white rounded-xl shadow-sm border border-gray-200 text-gray-600 hover:text-[#E76F51] hover:border-[#E76F51]/30 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-2xl text-sm font-medium">{{ session('status') }}</div>
        @endif

        {{-- New Chat button --}}
        <form action="{{ route('app.ai.create') }}" method="POST" class="mb-6">
            @csrf
            <button type="submit"
                    class="w-full py-4 gradient-accent text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-3 text-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Chat Baru dengan Chef AI
            </button>
        </form>

        {{-- Conversations list --}}
        @if(count($conversations) > 0)
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-gray-900">Riwayat Chat</h2>
                <form action="{{ route('app.ai.delete-all') }}" method="POST"
                      onsubmit="return confirm('Hapus semua riwayat chat?')">
                    @csrf
                    <button type="submit" class="text-sm text-red-500 font-semibold hover:text-red-700 transition-colors">
                        Hapus Semua
                    </button>
                </form>
            </div>

            @foreach($conversations as $conv)
                @php
                    try { $timeAgo = \Carbon\Carbon::parse($conv['updated_at'])->diffForHumans(); } catch(\Exception $e) { $timeAgo = ''; }
                    $providerColor = ($conv['provider'] ?? 'groq') === 'openrouter' ? 'bg-purple-100 text-purple-700' : 'bg-teal-100 text-teal-700';
                    $providerLabel = ($conv['provider'] ?? 'groq') === 'openrouter' ? 'OpenRouter' : 'Groq';
                @endphp
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all mb-3 overflow-hidden">
                    <a href="{{ route('app.ai.conversation', $conv['id']) }}" class="flex items-center gap-4 p-4">
                        <div class="w-12 h-12 gradient-accent rounded-2xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <p class="font-bold text-gray-900 truncate text-sm">{{ $conv['title'] ?? 'New Chat' }}</p>
                                <span class="text-xs text-gray-400 flex-shrink-0">{{ $timeAgo }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full {{ $providerColor }}">{{ $providerLabel }}</span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <div class="border-t border-gray-100 flex">
                        <form action="{{ route('app.ai.delete', $conv['id']) }}" method="POST" class="flex-1"
                              onsubmit="return confirm('Hapus chat ini?')">
                            @csrf
                            <button type="submit" class="w-full py-2.5 text-xs font-semibold text-red-500 hover:bg-red-50 transition-colors">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        @else
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm">
                <div class="w-24 h-24 gradient-accent rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Belum Ada Riwayat Chat</h3>
                <p class="text-gray-500 text-sm">Mulai chat dengan Chef AI Savora!</p>
            </div>
        @endif

    </div>
</body>
</html>
