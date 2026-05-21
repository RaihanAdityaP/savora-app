<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $conversation['title'] ?? 'Chef AI' }} — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        .msg-user {
            background: var(--gradient-accent);
            color: #ffffff;
        }
        .msg-ai {
            background: var(--color-chip-bg);
            color: var(--color-text-primary);
            border: 1px solid var(--color-card-border);
        }
        .msg-error {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.25);
            color: #ef4444;
        }
        .chat-input-area {
            background: var(--color-card-bg);
            border-top: 1px solid var(--color-separator);
        }
        .chat-textarea-wrap {
            background: var(--color-chip-bg);
            border: 1.5px solid var(--color-card-border);
            border-radius: 16px;
            transition: border-color .2s;
        }
        .chat-textarea-wrap:focus-within {
            border-color: var(--color-primary-coral);
        }
        .chat-textarea {
            background: transparent;
            color: var(--color-text-primary);
            width: 100%;
            resize: none;
            outline: none;
            font-size: 14px;
            line-height: 1.5;
        }
        .chat-textarea::placeholder { color: var(--color-text-muted); }
        .chat-header {
            background: var(--color-card-bg);
            border-bottom: 1px solid var(--color-separator);
        }
        .chat-back-btn {
            color: var(--color-text-primary);
            border-radius: 12px;
            padding: 8px;
            transition: background .2s;
        }
        .chat-back-btn:hover { background: var(--color-chip-bg); }
        .chat-settings-btn {
            color: var(--color-text-secondary);
            border-radius: 12px;
            padding: 8px;
            transition: background .2s, color .2s;
        }
        .chat-settings-btn:hover {
            background: var(--color-chip-bg);
            color: var(--color-primary-coral);
        }
    </style>
</head>
<body style="background: var(--color-bg-light); color: var(--color-text-primary); display: flex; flex-direction: column; height: 100vh;">

    {{-- Top bar --}}
    <header class="chat-header sticky top-0 z-50 flex-shrink-0">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center gap-3">
            <a href="{{ route('app.ai') }}" class="chat-back-btn">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>

            {{-- AI avatar --}}
            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                 style="background: var(--gradient-accent);">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>

            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm truncate" style="color: var(--color-text-primary);">
                    {{ $conversation['title'] ?? 'Chef AI' }}
                </p>
                <p class="text-xs" style="color: var(--color-text-secondary);">Tap untuk ganti judul</p>
            </div>

            <a href="{{ route('app.ai.settings') }}" class="chat-settings-btn">
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
                <div class="p-3 rounded-2xl text-sm msg-error">{{ session('error') }}</div>
            @endif

            {{-- Welcome message if empty --}}
            @if(count($messages) === 0)
                <div class="flex justify-start">
                    <div class="max-w-[80%] msg-ai rounded-2xl rounded-bl-sm px-4 py-3">
                        <p class="text-sm leading-relaxed">
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
                @php
                    $isUser  = ($msg['role'] ?? '') === 'user';
                    $isError = $msg['is_error'] ?? false;
                @endphp
                <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%] px-4 py-3 rounded-2xl
                        {{ $isUser ? 'msg-user rounded-br-sm' : ($isError ? 'msg-error rounded-bl-sm' : 'msg-ai rounded-bl-sm') }}">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $msg['content'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Input area --}}
    <div class="chat-input-area flex-shrink-0">
        <div class="max-w-3xl mx-auto px-4 py-3">
            <form action="{{ route('app.ai.send', $conversation['id']) }}" method="POST">
                @csrf
                <div class="flex items-end gap-3">
                    <div class="flex-1 chat-textarea-wrap px-4 py-3">
                        <textarea
                            name="content"
                            rows="1"
                            placeholder="Tanya tentang masak..."
                            class="chat-textarea"
                            style="max-height: 120px;"
                            onInput="this.style.height='auto'; this.style.height=this.scrollHeight+'px'"
                        ></textarea>
                    </div>
                    <button type="submit" class="btn-primary-savora flex-shrink-0 p-0"
                            style="width: 44px; height: 44px; border-radius: 50%;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const container = document.getElementById('messages-container');
        if (container) container.scrollTop = container.scrollHeight;
    </script>
</body>
</html>