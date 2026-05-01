<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $conversation['title'] ?? 'Chef AI' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .gradient-accent { background: linear-gradient(135deg, #E76F51, #F4A261); }
        .msg-user { background: linear-gradient(135deg, #E76F51, #F4A261); }
        .msg-ai   { background: #F3F4F6; }
    </style>
</head>
<body class="bg-[#F5F7FA] text-gray-900 flex flex-col h-screen">

    {{-- Top bar --}}
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm flex-shrink-0">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center gap-3">
            <a href="{{ route('app.ai') }}" class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div class="w-8 h-8 gradient-accent rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-gray-900 text-sm truncate">{{ $conversation['title'] ?? 'Chef AI' }}</p>
                <p class="text-xs text-gray-500">Tap untuk ganti judul</p>
            </div>
            <a href="{{ route('app.ai.settings') }}" class="p-2 rounded-full hover:bg-gray-100 transition-colors text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </a>
        </div>
    </header>

    {{-- Messages --}}
    <div class="flex-1 overflow-y-auto" id="messages-container">
        <div class="max-w-3xl mx-auto px-4 py-4 space-y-4">

            @if(session('error'))
                <div class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-sm">{{ session('error') }}</div>
            @endif

            {{-- Welcome message if empty --}}
            @if(count($messages) === 0)
                <div class="flex justify-start">
                    <div class="max-w-[80%] bg-gray-100 rounded-2xl rounded-bl-sm px-4 py-3">
                        <p class="text-gray-800 text-sm leading-relaxed">
                            Halo! Saya Chef AI Savora 👨‍🍳<br><br>
                            Saya siap membantu Anda dengan:<br>
                            • Pertanyaan tentang resep<br>
                            • Tips dan teknik memasak<br>
                            • Saran variasi resep<br><br>
                            Ada yang bisa saya bantu?
                        </p>
                    </div>
                </div>
            @endif

            @foreach($messages as $msg)
                @php $isUser = ($msg['role'] ?? '') === 'user'; $isError = $msg['is_error'] ?? false; @endphp
                <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%] px-4 py-3 rounded-2xl {{ $isUser ? 'msg-user text-white rounded-br-sm' : ($isError ? 'bg-red-50 border border-red-200 text-red-800 rounded-bl-sm' : 'msg-ai text-gray-800 rounded-bl-sm') }}">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $msg['content'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Input area --}}
    <div class="flex-shrink-0 bg-white border-t border-gray-200 shadow-lg">
        <div class="max-w-3xl mx-auto px-4 py-3">
            <form action="{{ route('app.ai.send', $conversation['id']) }}" method="POST">
                @csrf
                <div class="flex items-end gap-3">
                    <div class="flex-1 bg-gray-100 rounded-2xl px-4 py-3">
                        <textarea name="content" rows="1" placeholder="Tanya tentang masak..."
                                  class="w-full bg-transparent text-sm text-gray-900 placeholder-gray-400 resize-none focus:outline-none"
                                  style="max-height: 120px"
                                  onInput="this.style.height='auto'; this.style.height=this.scrollHeight+'px'"></textarea>
                    </div>
                    <button type="submit"
                            class="w-11 h-11 gradient-accent rounded-full flex items-center justify-center text-white shadow-lg hover:shadow-xl transition-all flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom
        const container = document.getElementById('messages-container');
        if (container) container.scrollTop = container.scrollHeight;
    </script>
</body>
</html>
