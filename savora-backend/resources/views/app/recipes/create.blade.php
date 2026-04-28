<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Resep Baru — Savora</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>.gradient-accent{background:linear-gradient(135deg,#E76F51,#F4A261)}</style>
</head>
<body class="bg-[#F5F7FA] text-gray-900">

    <x-unified-navigation
        :avatar-url="session('user_avatar')"
        :unread-count="0"
        :username="session('user_username')"
    />

    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10" x-data="recipeForm()">

        {{-- Header --}}
        <div class="relative rounded-3xl overflow-hidden mb-5 shadow-xl gradient-accent p-5 text-white">
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
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-2xl text-sm">{{ session('error') ?? $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('app.recipe.store') }}" enctype="multipart/form-data" @submit="handleSubmit">
            @csrf

            {{-- Image --}}
            <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 mb-4">
                <div class="relative h-44 gradient-accent">
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

            {{-- Basic info --}}
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4 space-y-4">
                <h2 class="font-bold text-gray-900 flex items-center gap-2 text-sm">
                    <div class="w-1 h-4 gradient-accent rounded-full"></div>Informasi Dasar
                </h2>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wide">Judul Resep *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Judul resep yang menarik"
                           class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white text-sm transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wide">Deskripsi *</label>
                    <textarea name="description" rows="3" required placeholder="Ceritakan tentang resep Anda..."
                              class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] focus:bg-white text-sm resize-none transition-all">{{ old('description') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wide">Kategori *</label>
                        <select name="category_id" required class="w-full px-3 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] text-sm">
                            <option value="">Pilih...</option>
                            @foreach($categories ?? [] as $cat)
                                <option value="{{ $cat['id'] }}" {{ old('category_id') == $cat['id'] ? 'selected' : '' }}>{{ $cat['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wide">Kesulitan</label>
                        <select name="difficulty" class="w-full px-3 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] text-sm">
                            <option value="mudah" {{ old('difficulty','mudah')=='mudah'?'selected':'' }}>Mudah</option>
                            <option value="sedang" {{ old('difficulty')=='sedang'?'selected':'' }}>Sedang</option>
                            <option value="sulit"  {{ old('difficulty')=='sulit'?'selected':'' }}>Sulit</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wide">Waktu (mnt)</label>
                        <input type="number" name="cooking_time" value="{{ old('cooking_time') }}" placeholder="30"
                               class="w-full px-3 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wide">Porsi</label>
                        <input type="number" name="servings" value="{{ old('servings') }}" placeholder="4"
                               class="w-full px-3 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase tracking-wide">Kalori</label>
                        <input type="number" name="calories" value="{{ old('calories') }}" placeholder="500"
                               class="w-full px-3 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] text-sm">
                    </div>
                </div>
            </div>

            {{-- Ingredients --}}
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
                <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                    <div class="w-1 h-4 gradient-accent rounded-full"></div>Bahan-bahan
                </h2>
                <div class="flex gap-2 mb-3">
                    <input type="text" x-model="newIngredient" placeholder="Tambahkan bahan..." @keydown.enter.prevent="addIngredient()"
                           class="flex-1 min-w-0 px-4 py-2.5 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] text-sm">
                    <button type="button" @click="addIngredient()" class="px-4 py-2.5 gradient-accent text-white font-bold rounded-2xl text-sm flex-shrink-0">+</button>
                </div>
                <div class="space-y-2">
                    <template x-for="(item, i) in ingredients" :key="i">
                        <div class="flex items-center gap-2 bg-gray-50 px-3 py-2.5 rounded-xl">
                            <span class="w-5 h-5 gradient-accent rounded-full flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0" x-text="i+1"></span>
                            <span class="flex-1 text-sm text-gray-700 min-w-0 truncate" x-text="item"></span>
                            <button type="button" @click="ingredients.splice(i,1)" class="text-red-400 hover:text-red-600 flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <p x-show="ingredients.length===0" class="text-gray-400 text-xs text-center py-1">Belum ada bahan</p>
                </div>
            </div>

            {{-- Steps --}}
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-4">
                <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                    <div class="w-1 h-4 gradient-accent rounded-full"></div>Langkah-langkah
                </h2>
                <div class="flex gap-2 mb-3">
                    <input type="text" x-model="newStep" placeholder="Tambahkan langkah..." @keydown.enter.prevent="addStep()"
                           class="flex-1 min-w-0 px-4 py-2.5 bg-gray-50 rounded-2xl border border-gray-200 focus:outline-none focus:border-[#E76F51] text-sm">
                    <button type="button" @click="addStep()" class="px-4 py-2.5 gradient-accent text-white font-bold rounded-2xl text-sm flex-shrink-0">+</button>
                </div>
                <div class="space-y-2">
                    <template x-for="(step, i) in steps" :key="i">
                        <div class="flex items-start gap-2 bg-gray-50 px-3 py-2.5 rounded-xl">
                            <span class="w-5 h-5 gradient-accent rounded-lg flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 mt-0.5" x-text="i+1"></span>
                            <span class="flex-1 text-sm text-gray-700 leading-relaxed min-w-0" x-text="step"></span>
                            <button type="button" @click="steps.splice(i,1)" class="text-red-400 hover:text-red-600 flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                    <p x-show="steps.length===0" class="text-gray-400 text-xs text-center py-1">Belum ada langkah</p>
                </div>
            </div>

            {{-- Tags --}}
            <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 mb-5">
                <h2 class="font-bold text-gray-900 mb-3 flex items-center gap-2 text-sm">
                    <div class="w-1 h-4 gradient-accent rounded-full"></div>Tag (opsional)
                </h2>
                <div class="flex flex-wrap gap-2" x-data="{ sel: [] }">
                    @foreach($popularTags ?? [] as $tag)
                        <button type="button"
                                @click="sel.includes({{ $tag['id'] }}) ? sel.splice(sel.indexOf({{ $tag['id'] }}),1) : sel.push({{ $tag['id'] }})"
                                :class="sel.includes({{ $tag['id'] }}) ? 'gradient-accent text-white' : 'bg-gray-100 text-gray-700'"
                                class="px-3 py-1.5 rounded-full text-xs font-semibold transition-all">
                            #{{ $tag['name'] }}
                        </button>
                        <template x-if="sel.includes({{ $tag['id'] }})">
                            <input type="hidden" name="tags[]" value="{{ $tag['id'] }}">
                        </template>
                    @endforeach
                </div>
                <a href="{{ route('app.tags') }}" class="inline-flex items-center gap-1 mt-3 text-xs text-[#E76F51] font-semibold">
                    + Buat tag baru
                </a>
            </div>

            <button type="submit" :disabled="isSubmitting"
                    class="w-full py-4 gradient-accent text-white font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all text-sm disabled:opacity-50">
                <span x-show="!isSubmitting">Buat Resep</span>
                <span x-show="isSubmitting" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    Membuat...
                </span>
            </button>
        </form>
    </div>

    <script>
    function recipeForm() {
        return {
            imagePreview: null, ingredients: [], steps: [], newIngredient: '', newStep: '', isSubmitting: false,
            handleImage(e) {
                const f = e.target.files[0]; if (!f) return;
                const r = new FileReader(); r.onload = ev => this.imagePreview = ev.target.result; r.readAsDataURL(f);
            },
            addIngredient() { const v = this.newIngredient.trim(); if (v) { this.ingredients.push(v); this.newIngredient = ''; } },
            addStep()       { const v = this.newStep.trim();       if (v) { this.steps.push(v);       this.newStep = '';       } },
            handleSubmit(e) {
                this.isSubmitting = true;
                this.ingredients.forEach((v,i) => { const inp = document.createElement('input'); inp.type='hidden'; inp.name=`ingredients[${i}]`; inp.value=v; e.target.appendChild(inp); });
                this.steps.forEach((v,i)       => { const inp = document.createElement('input'); inp.type='hidden'; inp.name=`steps[${i}]`;       inp.value=v; e.target.appendChild(inp); });
            }
        }
    }
    </script>
</body>
</html>
