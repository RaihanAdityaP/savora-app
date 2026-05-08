<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Resep Baru — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @include('components.app-theme')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');
        body { font-family: 'Inter', sans-serif; }
        h1, h2 { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="min-h-screen" style="background: var(--color-bg-light);">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10" x-data="recipeForm()" @submit.prevent="handleSubmit">

        {{-- Header --}}
        <div class="relative rounded-3xl overflow-hidden mb-5 shadow-xl p-5 text-white" style="background: var(--gradient-accent);">
            <div class="absolute -top-10 -right-10 w-28 h-28 bg-white opacity-10 rounded-full pointer-events-none"></div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-white/25 rounded-2xl border-2 border-white/40">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold">Buat Resep Baru</h1>
                    <p class="text-white/80 text-xs">Bagikan resep lezatmu ke komunitas</p>
                </div>
            </div>
        </div>

        @if(session('error') || $errors->any())
            <div class="mb-4">
                <x-app-theme.info-banner
                    message="{{ session('error') ?? $errors->first() }}"
                    icon="bi bi-exclamation-circle" />
            </div>
        @endif

        <form method="POST" action="{{ route('app.recipe.store') }}" enctype="multipart/form-data" @submit="handleSubmit">
            @csrf

            {{-- Image --}}
            <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 mb-4">
                <div class="relative h-44" style="background: var(--gradient-accent);">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div x-show="!imagePreview" class="text-center text-white">
                            <svg class="w-10 h-10 mx-auto mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm font-semibold">Pilih Gambar Resep</p>
                        </div>
                        <img x-show="imagePreview" :src="imagePreview" class="w-full h-full object-cover" alt="Preview">
                    </div>
                    <label class="absolute bottom-3 right-3 cursor-pointer">
                        <input type="file" name="image" accept="image/*" @change="handleImage" class="hidden">
                        <span class="flex items-center gap-1.5 bg-white/20 text-white text-xs font-bold px-3 py-2 rounded-full border border-white/30 hover:bg-white/30 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Upload
                        </span>
                    </label>
                </div>
            </div>

            {{-- Video (optional) --}}
            <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 mb-4">
                <div class="relative h-32" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div x-show="!videoFileName" class="text-center text-white">
                            <svg class="w-8 h-8 mx-auto mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-semibold">Tambahkan Video (Opsional)</p>
                        </div>
                        <div x-show="videoFileName" class="text-center text-white">
                            <svg class="w-8 h-8 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <p class="text-sm font-semibold" x-text="videoFileName"></p>
                        </div>
                    </div>
                    <div class="absolute bottom-3 right-3 flex gap-2">
                        <label class="cursor-pointer" x-show="!videoFileName">
                            <input type="file" name="video" accept="video/*" @change="pickVideo" class="hidden">
                            <span class="flex items-center gap-1.5 bg-white/20 text-white text-xs font-bold px-3 py-2 rounded-full border border-white/30 hover:bg-white/30 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Pilih Video
                            </span>
                        </label>
                        <button type="button" @click="removeVideo" x-show="videoFileName"
                                class="flex items-center gap-1.5 bg-red-500/80 hover:bg-red-600 text-white text-xs font-bold px-3 py-2 rounded-full border border-red-400/50 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Hapus
                        </button>
                    </div>
                </div>
            </div>

            {{-- Basic info --}}
            <div class="card-savora p-5 mb-4 space-y-4">
                <x-app-theme.section-header title="Informasi Dasar" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' />
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">Judul Resep *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Judul resep yang menarik" class="input-savora">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">Deskripsi *</label>
                    <textarea name="description" rows="3" required
                              placeholder="Ceritakan tentang resep Anda..."
                              class="input-savora resize-none">{{ old('description') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">Kategori *</label>
                        <select name="category_id" required class="input-savora">
                            <option value="">Pilih...</option>
                            @foreach($categories ?? [] as $cat)
                                <option value="{{ $cat['id'] }}" {{ old('category_id') == $cat['id'] ? 'selected' : '' }}>{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">Kesulitan</label>
                        <select name="difficulty" class="input-savora">
                            <option value="mudah" {{ old('difficulty','mudah')=='mudah'?'selected':'' }}>Mudah</option>
                            <option value="sedang" {{ old('difficulty')=='sedang'?'selected':'' }}>Sedang</option>
                            <option value="sulit"  {{ old('difficulty')=='sulit'?'selected':'' }}>Sulit</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">Waktu (mnt)</label>
                        <input type="number" name="cooking_time" value="{{ old('cooking_time') }}" placeholder="30" class="input-savora">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">Porsi</label>
                        <input type="number" name="servings" value="{{ old('servings') }}" placeholder="4" class="input-savora">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">Kalori</label>
                        <input type="number" name="calories" value="{{ old('calories') }}" placeholder="500" class="input-savora">
                    </div>
                </div>
            </div>

            {{-- Ingredients --}}
            <div class="card-savora p-5 mb-4">
                <div class="mb-3">
                    <x-app-theme.section-header title="Bahan-bahan" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>' />
                </div>
                <div class="flex gap-2 mb-3">
                    <input type="text" x-model="newIngredient" placeholder="Tambahkan bahan..."
                           @keydown.enter.prevent="addIngredient()" class="input-savora flex-1 min-w-0">
                    <button type="button" @click="addIngredient()" class="btn-primary-savora px-4 py-2.5 shrink-0">+</button>
                </div>
                <div class="space-y-2">
                    <template x-for="(item, i) in ingredients" :key="i">
                        <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl" style="background: var(--color-bg-light);">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0"
                                  style="background: var(--gradient-accent);" x-text="i+1"></span>
                            <span class="flex-1 text-sm min-w-0 truncate" style="color: var(--color-text-primary);" x-text="item"></span>
                            <button type="button" @click="ingredients.splice(i,1)" class="text-red-400 hover:text-red-600 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <p x-show="ingredients.length===0" class="text-center py-1 text-xs" style="color: var(--color-text-secondary);">Belum ada bahan</p>
                </div>
            </div>

            {{-- Steps --}}
            <div class="card-savora p-5 mb-4">
                <div class="mb-3">
                    <x-app-theme.section-header title="Langkah-langkah" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h8M3 6h.01M3 12h.01M3 18h.01"/></svg>' />
                </div>
                <div class="flex gap-2 mb-3">
                    <input type="text" x-model="newStep" placeholder="Tambahkan langkah..."
                           @keydown.enter.prevent="addStep()" class="input-savora flex-1 min-w-0">
                    <button type="button" @click="addStep()" class="btn-primary-savora px-4 py-2.5 shrink-0">+</button>
                </div>
                <div class="space-y-2">
                    <template x-for="(step, i) in steps" :key="i">
                        <div class="flex items-start gap-2 px-3 py-2.5 rounded-xl" style="background: var(--color-bg-light);">
                            <span class="w-5 h-5 rounded-lg flex items-center justify-center text-white text-[10px] font-bold shrink-0 mt-0.5"
                                  style="background: var(--gradient-accent);" x-text="i+1"></span>
                            <span class="flex-1 text-sm leading-relaxed min-w-0" style="color: var(--color-text-primary);" x-text="step"></span>
                            <button type="button" @click="steps.splice(i,1)" class="text-red-400 hover:text-red-600 shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <p x-show="steps.length===0" class="text-center py-1 text-xs" style="color: var(--color-text-secondary);">Belum ada langkah</p>
                </div>
            </div>

            {{-- Tags --}}
            <div class="card-savora p-5 mb-5">
                <div class="mb-3">
                    <x-app-theme.section-header title="Tag (opsional)" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>' />
                </div>
                <div class="flex flex-wrap gap-2" x-data="{ sel: [] }">
                    @foreach($popularTags ?? [] as $tag)
                        <button type="button"
                                @click="sel.includes({{ $tag['id'] }}) ? sel.splice(sel.indexOf({{ $tag['id'] }}),1) : sel.push({{ $tag['id'] }})"
                                :class="sel.includes({{ $tag['id'] }}) ? 'selected' : ''"
                                class="tag-chip">
                            #{{ $tag['name'] }}
                        </button>
                        <template x-if="sel.includes({{ $tag['id'] }})">
                            <input type="hidden" name="tags[]" value="{{ $tag['id'] }}">
                        </template>
                    @endforeach
                </div>
                <a href="{{ route('app.tags') }}"
                   class="inline-flex items-center gap-1 mt-3 text-xs font-semibold"
                   style="color: var(--color-primary-coral);">+ Buat tag baru</a>
            </div>

            <button type="submit" :disabled="isSubmitting" class="btn-primary-savora w-full py-4 rounded-2xl">
                <span x-show="!isSubmitting">Buat Resep</span>
                <span x-show="isSubmitting" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    Membuat...
                </span>
            </button>
        </form>
    </div>

    <script>
    function recipeForm() {
        return {
            imagePreview: null, videoFileName: null, ingredients: [], steps: [], newIngredient: '', newStep: '', isSubmitting: false,
            handleImage(e) {
                const f = e.target.files[0]; if (!f) return;
                const r = new FileReader(); r.onload = ev => this.imagePreview = ev.target.result; r.readAsDataURL(f);
            },
            pickVideo(e) {
                const f = e.target.files[0]; if (!f) return;
                if (f.size > 50 * 1024 * 1024) { alert('Video terlalu besar! Maksimal 50MB'); return; }
                this.videoFileName = f.name;
            },
            removeVideo() { this.videoFileName = null; const inp = document.querySelector('input[name="video"]'); if (inp) inp.value = ''; },
            addIngredient() { const v = this.newIngredient.trim(); if (v) { this.ingredients.push(v); this.newIngredient = ''; } },
            addStep()       { const v = this.newStep.trim();       if (v) { this.steps.push(v);       this.newStep = '';       } },
            handleSubmit(e) {
                if (!this.imagePreview && !document.querySelector('input[name="image"]').files.length) { alert('Pilih gambar resep terlebih dahulu'); return; }
                if (this.ingredients.length === 0) { alert('Tambahkan minimal 1 bahan'); return; }
                if (this.steps.length === 0) { alert('Tambahkan minimal 1 langkah'); return; }
                this.isSubmitting = true;
                this.ingredients.forEach((v,i) => { const inp = document.createElement('input'); inp.type='hidden'; inp.name=`ingredients[${i}]`; inp.value=v; e.target.appendChild(inp); });
                this.steps.forEach((v,i)       => { const inp = document.createElement('input'); inp.type='hidden'; inp.name=`steps[${i}]`;       inp.value=v; e.target.appendChild(inp); });
            }
        }
    }
    </script>
</body>
</html>