@php($isEnglish = session('user_language', 'en') === 'en')
<!DOCTYPE html>

<html lang="{{ $isEnglish ? 'en' : 'id' }}">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $isEnglish ? 'Create New Recipe' : 'Buat Resep Baru' }} — Savora</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @include('components.app-theme')

    <style>

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&display=swap');

        body { font-family: 'Inter', sans-serif; }

        h1, h2 { font-family: 'Poppins', sans-serif; }



        /* Tag modal backdrop */

        .tag-modal-backdrop {

            background: rgba(0,0,0,0.5);

            backdrop-filter: blur(4px);

        }



        /* Draft indicator pulse */

        @keyframes saved-pulse {

            0%   { opacity: 1; }

            50%  { opacity: 0.5; }

            100% { opacity: 1; }

        }

        .draft-saving { animation: saved-pulse 1s ease-in-out 2; }

    </style>

</head>

<body class="min-h-screen" style="background: var(--color-bg-light);">



    <x-unified-navigation

        :avatar-url="session('user_avatar')"

        :unread-count="0"

        :username="session('user_username')"

    />



    <div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-10"

         x-data="recipeForm()"

         x-init="initDraft()"

         @keydown.escape.window="showTagModal = false">



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

                    <h1 class="text-xl font-bold">{{ $isEnglish ? 'Create New Recipe' : 'Buat Resep Baru' }}</h1>

                    <p class="text-white/80 text-xs">{{ $isEnglish ? 'Share your delicious recipe with the community' : 'Bagikan resep lezatmu ke komunitas' }}</p>

                </div>

                {{-- Draft indicator --}}

                <div class="ml-auto flex items-center gap-1.5 bg-white/20 rounded-full px-3 py-1"

                     x-show="lastSaved" x-transition>

                    <svg class="w-3 h-3 text-white/80" fill="currentColor" viewBox="0 0 20 20">

                        <path d="M17.293 3.293a1 1 0 011.414 1.414l-10 10a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l9.293-9.293z"/>

                    </svg>

                    <span class="text-white/80 text-[10px]" x-text="'{{ $isEnglish ? 'Saved ' : 'Tersimpan ' }}' + lastSaved"></span>

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



        <form method="POST" action="{{ route('app.recipe.store') }}" enctype="multipart/form-data"

              @submit="handleSubmit" x-ref="form">

            @csrf



            {{-- Image --}}

            <div class="bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100 mb-4">

                <div class="relative h-44" style="background: var(--gradient-accent);">

                    <div class="absolute inset-0 flex items-center justify-center">

                        <div x-show="!imagePreview" class="text-center text-white">

                            <svg class="w-10 h-10 mx-auto mb-1 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>

                            </svg>

                            <p class="text-sm font-semibold">{{ $isEnglish ? 'Choose Recipe Image' : 'Pilih Gambar Resep' }}</p>

                        </div>

                        <img x-show="imagePreview" :src="imagePreview" class="w-full h-full object-cover" alt="Preview">

                    </div>

                    <label class="absolute bottom-3 right-3 cursor-pointer">

                        {{-- Store actual file in a hidden input via DataTransfer trick --}}

                        <input type="file" name="image" accept="image/*" @change="handleImage($event)" class="hidden" x-ref="imageInput">

                        <span class="flex items-center gap-1.5 bg-white/20 text-white text-xs font-bold px-3 py-2 rounded-full border border-white/30 hover:bg-white/30 transition-all">

                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>

                            </svg>

                            <span x-text="imagePreview ? 'Ganti' : 'Upload'"></span>

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

                            <p class="text-sm font-semibold">{{ $isEnglish ? 'Add Video (Optional)' : 'Tambahkan Video (Opsional)' }}</p>

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

                            <input type="file" name="video" accept="video/*" @change="pickVideo($event)" class="hidden" x-ref="videoInput">

                            <span class="flex items-center gap-1.5 bg-white/20 text-white text-xs font-bold px-3 py-2 rounded-full border border-white/30 hover:bg-white/30 transition-all">

                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>

                                </svg>

                                {{ $isEnglish ? 'Choose Video' : 'Pilih Video' }}

                            </span>

                        </label>

                        <button type="button" @click="removeVideo()" x-show="videoFileName"

                                class="flex items-center gap-1.5 bg-red-500/80 hover:bg-red-600 text-white text-xs font-bold px-3 py-2 rounded-full border border-red-400/50 transition-all">

                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>

                            </svg>

                            {{ $isEnglish ? 'Remove' : 'Hapus' }}

                        </button>

                    </div>

                </div>

            </div>



            {{-- Basic info --}}

            <div class="card-savora p-5 mb-4 space-y-4">

                <x-app-theme.section-header :title="$isEnglish ? 'Basic Information' : 'Informasi Dasar'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' />

                <div>

                    <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Recipe Title *' : 'Judul Resep *' }}</label>

                    <input type="text" name="title" x-model="draft.title" @input="scheduleSave()" required

                           placeholder="{{ $isEnglish ? 'An appealing recipe title' : 'Judul resep yang menarik' }}" class="input-savora">

                </div>

                <div>

                    <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Description *' : 'Deskripsi *' }}</label>

                    <textarea name="description" rows="3" required x-model="draft.description" @input="scheduleSave()"

                              placeholder="{{ $isEnglish ? 'Tell people about your recipe...' : 'Ceritakan tentang resep Anda...' }}"

                              class="input-savora resize-none"></textarea>

                </div>

                <div class="grid grid-cols-2 gap-3">

                    <div>

                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Category *' : 'Kategori *' }}</label>

                        <select name="category_id" required class="input-savora" x-model="draft.category_id" @change="scheduleSave()">

                            <option value="">{{ $isEnglish ? 'Choose...' : 'Pilih...' }}</option>

                            @foreach($categories ?? [] as $cat)

                                <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>

                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Difficulty' : 'Kesulitan' }}</label>

                        <select name="difficulty" class="input-savora" x-model="draft.difficulty" @change="scheduleSave()">

                            <option value="mudah">{{ $isEnglish ? 'Easy' : 'Mudah' }}</option>

                            <option value="sedang">{{ $isEnglish ? 'Medium' : 'Sedang' }}</option>

                            <option value="sulit">{{ $isEnglish ? 'Hard' : 'Sulit' }}</option>

                        </select>

                    </div>

                </div>

                <div class="grid grid-cols-3 gap-3">

                    <div>

                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Time (min)' : 'Waktu (mnt)' }}</label>

                        <input type="number" name="cooking_time" x-model="draft.cooking_time" @input="scheduleSave()" placeholder="30" class="input-savora">

                    </div>

                    <div>

                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Servings' : 'Porsi' }}</label>

                        <input type="number" name="servings" x-model="draft.servings" @input="scheduleSave()" placeholder="4" class="input-savora">

                    </div>

                    <div>

                        <label class="block text-xs font-bold uppercase tracking-wide mb-1.5" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Calories' : 'Kalori' }}</label>

                        <input type="number" name="calories" x-model="draft.calories" @input="scheduleSave()" placeholder="500" class="input-savora">

                    </div>

                </div>

            </div>



            {{-- Ingredients --}}

            <div class="card-savora p-5 mb-4">

                <div class="mb-3">

                    <x-app-theme.section-header :title="$isEnglish ? 'Ingredients' : 'Bahan-bahan'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>' />

                </div>

                <div class="flex gap-2 mb-3">

                    <input type="text" x-model="newIngredient" placeholder="{{ $isEnglish ? 'Add ingredient...' : 'Tambahkan bahan...' }}"

                           @keydown.enter.prevent="addIngredient()" class="input-savora flex-1 min-w-0">

                    <button type="button" @click="addIngredient()" class="btn-primary-savora px-4 py-2.5 shrink-0">+</button>

                </div>

                <div class="space-y-2">

                    <template x-for="(item, i) in draft.ingredients" :key="i">

                        <div class="flex items-center gap-2 px-3 py-2.5 rounded-xl" style="background: var(--color-bg-light);">

                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0"

                                  style="background: var(--gradient-accent);" x-text="i+1"></span>

                            <span class="flex-1 text-sm min-w-0 truncate" style="color: var(--color-text-primary);" x-text="item"></span>

                            <button type="button" @click="draft.ingredients.splice(i,1); scheduleSave()" class="text-red-400 hover:text-red-600 shrink-0">

                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>

                            </button>

                        </div>

                    </template>

                    <p x-show="draft.ingredients.length===0" class="text-center py-1 text-xs" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'No ingredients yet' : 'Belum ada bahan' }}</p>

                </div>

            </div>



            {{-- Steps --}}

            <div class="card-savora p-5 mb-4">

                <div class="mb-3">

                    <x-app-theme.section-header :title="$isEnglish ? 'Steps' : 'Langkah-langkah'" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h8M3 6h.01M3 12h.01M3 18h.01"/></svg>' />

                </div>

                <div class="flex gap-2 mb-3">

                    <input type="text" x-model="newStep" placeholder="{{ $isEnglish ? 'Add step...' : 'Tambahkan langkah...' }}"

                           @keydown.enter.prevent="addStep()" class="input-savora flex-1 min-w-0">

                    <button type="button" @click="addStep()" class="btn-primary-savora px-4 py-2.5 shrink-0">+</button>

                </div>

                <div class="space-y-2">

                    <template x-for="(step, i) in draft.steps" :key="i">

                        <div class="flex items-start gap-2 px-3 py-2.5 rounded-xl" style="background: var(--color-bg-light);">

                            <span class="w-5 h-5 rounded-lg flex items-center justify-center text-white text-[10px] font-bold shrink-0 mt-0.5"

                                  style="background: var(--gradient-accent);" x-text="i+1"></span>

                            <span class="flex-1 text-sm leading-relaxed min-w-0" style="color: var(--color-text-primary);" x-text="step"></span>

                            <button type="button" @click="draft.steps.splice(i,1); scheduleSave()" class="text-red-400 hover:text-red-600 shrink-0 mt-0.5">

                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>

                            </button>

                        </div>

                    </template>

                    <p x-show="draft.steps.length===0" class="text-center py-1 text-xs" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'No steps yet' : 'Belum ada langkah' }}</p>

                </div>

            </div>



            {{-- Tags — MAX 3 --}}

            <div class="card-savora p-5 mb-5">

                <div class="mb-3 flex items-center justify-between">

                    <x-app-theme.section-header title="Tag (opsional)" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>' />

                    {{-- Counter badge --}}

                    <span class="text-xs font-bold px-2.5 py-1 rounded-full"

                          :class="draft.selectedTags.length >= 3 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-500'"

                          x-text="draft.selectedTags.length + '/3 tag'"></span>

                </div>



                {{-- Selected tags display --}}

                <div class="flex flex-wrap gap-2 mb-3" x-show="draft.selectedTags.length > 0">

                    <template x-for="(tag, i) in draft.selectedTags" :key="tag.id">

                        <span class="tag-chip selected flex items-center gap-1.5">

                            #<span x-text="tag.name"></span>

                            <button type="button" @click="removeTag(i)"

                                    class="ml-0.5 hover:opacity-70 transition-opacity">

                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>

                            </button>

                        </span>

                    </template>

                </div>



                {{-- Hidden inputs for form submit --}}

                <template x-for="tag in draft.selectedTags" :key="tag.id">

                    <input type="hidden" name="tags[]" :value="tag.id">

                </template>



                {{-- Popular tags quick-select --}}

                <div x-show="draft.selectedTags.length < 3">

                    <p class="text-xs font-semibold mb-2" style="color: var(--color-text-secondary);">Tag Populer</p>

                    <div class="flex flex-wrap gap-2">

                        @foreach($popularTags ?? [] as $tag)

                            <button type="button"

                                    @click="togglePopularTag({ id: {{ $tag['id'] }}, name: '{{ addslashes($tag['name']) }}' })"

                                    :class="isTagSelected({{ $tag['id'] }}) ? 'selected' : (draft.selectedTags.length >= 3 ? 'opacity-40 cursor-not-allowed' : '')"

                                    :disabled="!isTagSelected({{ $tag['id'] }}) && draft.selectedTags.length >= 3"

                                    class="tag-chip">

                                #{{ $tag['name'] }}

                            </button>

                        @endforeach

                    </div>

                </div>



                {{-- Max reached message --}}

                <p x-show="draft.selectedTags.length >= 3"

                   class="text-xs text-orange-500 font-medium mt-1">

                    Maksimal 3 tag sudah dipilih

                </p>



                {{-- Kelola tag button — opens modal, NOT navigation --}}

                <button type="button" @click="openTagModal()"

                        class="inline-flex items-center gap-1 mt-3 text-xs font-semibold"

                        style="color: var(--color-primary-coral);">

                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>

                    Kelola tag komunitas

                </button>

            </div>



            {{-- Draft info + Submit --}}

            <div class="flex items-center gap-2 mb-3 px-1">

                <svg class="w-4 h-4 shrink-0" style="color: var(--color-text-secondary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>

                </svg>

                <p class="text-xs" style="color: var(--color-text-secondary);">

                    Draft tersimpan otomatis ke browser — <button type="button" @click="clearDraft()" class="underline hover:opacity-70">hapus draft</button>

                </p>

            </div>



            <button type="submit" :disabled="isSubmitting" class="btn-primary-savora w-full py-4 rounded-2xl">

                <span x-show="!isSubmitting">{{ $isEnglish ? 'Create Recipe' : 'Buat Resep' }}</span>

                <span x-show="isSubmitting" class="flex items-center justify-center gap-2">

                    <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>

                    {{ $isEnglish ? 'Creating...' : 'Membuat...' }}

                </span>

            </button>

        </form>



        {{-- ── TAG MANAGEMENT MODAL (in-page, no navigation = files stay) ── --}}

        <div x-show="showTagModal" x-transition:enter="transition ease-out duration-200"

             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"

             x-transition:leave="transition ease-in duration-150"

             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"

             class="fixed inset-0 z-50 tag-modal-backdrop flex items-end md:items-center justify-center p-4"

             @click.self="showTagModal = false">



            <div class="bg-white rounded-3xl w-full max-w-lg max-h-[85vh] flex flex-col shadow-2xl"

                 x-transition:enter="transition ease-out duration-200"

                 x-transition:enter-start="opacity-0 translate-y-8"

                 x-transition:enter-end="opacity-100 translate-y-0">



                {{-- Modal header --}}

                <div class="flex items-center justify-between p-5 border-b border-gray-100">

                    <div class="flex items-center gap-3">

                        <div class="p-2 rounded-xl" style="background: var(--gradient-accent);">

                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>

                        </div>

                        <div>

                            <h3 class="font-bold text-sm" style="color: var(--color-text-primary);">{{ $isEnglish ? 'Manage Tags' : 'Kelola Tag' }}</h3>

                            <p class="text-xs" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'Select or create new tags' : 'Pilih atau buat tag baru' }} <span class="font-bold" :class="draft.selectedTags.length>=3 ? 'text-orange-500' : ''" x-text="'(' + draft.selectedTags.length + '/3)'"></span></p>

                        </div>

                    </div>

                    <button type="button" @click="showTagModal = false"

                            class="p-2 rounded-xl hover:bg-gray-100 transition-colors">

                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>

                    </button>

                </div>



                {{-- Search input --}}

                <div class="p-4 border-b border-gray-100">

                    <div class="relative">

                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>

                        <input type="text" x-model="tagSearch" @input="searchTags()" placeholder="{{ $isEnglish ? 'Search tags...' : 'Cari tag...' }}"

                               class="input-savora pl-9 pr-4 py-2.5 text-sm">

                    </div>

                </div>



                {{-- Create new tag --}}

                <div class="px-4 py-3 border-b border-gray-100" x-show="tagSearch.trim().length >= 2 && !tagSearchLoading">

                    <button type="button"

                            @click="createAndSelectTag()"

                            :disabled="draft.selectedTags.length >= 3"

                            class="flex items-center gap-2 w-full text-sm font-semibold px-3 py-2.5 rounded-xl transition-all"

                            :class="draft.selectedTags.length >= 3 ? 'text-gray-400 bg-gray-50 cursor-not-allowed' : 'hover:bg-orange-50'"

                            style="color: var(--color-primary-coral);">

                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>

                        <span>{{ $isEnglish ? 'Create tag' : 'Buat tag' }} "<span x-text="tagSearch.trim()"></span>"</span>

                    </button>

                    <p class="text-[10px] mt-1 ml-3" style="color: var(--color-text-secondary);">{{ $isEnglish ? 'New tags will wait for admin approval' : 'Tag baru akan menunggu persetujuan admin' }}</p>

                </div>



                {{-- Tag list --}}

                <div class="flex-1 overflow-y-auto p-4 space-y-1">

                    {{-- Loading --}}

                    <div x-show="tagSearchLoading" class="flex justify-center py-6">

                        <svg class="animate-spin w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>

                    </div>



                    {{-- Results --}}

                    <template x-for="tag in modalTagList" :key="tag.id">

                        <button type="button"

                                @click="toggleModalTag(tag)"

                                :disabled="!isTagSelected(tag.id) && draft.selectedTags.length >= 3"

                                class="flex items-center justify-between w-full px-4 py-3 rounded-2xl text-sm transition-all"

                                :class="isTagSelected(tag.id) ? 'font-semibold' : (draft.selectedTags.length >= 3 ? 'opacity-40 cursor-not-allowed text-gray-600' : 'hover:bg-gray-50 text-gray-700')"

                                :style="isTagSelected(tag.id) ? 'background: var(--color-primary-coral); color: white;' : ''">

                            <div class="flex items-center gap-2">

                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>

                                <span x-text="tag.name"></span>

                                <span x-show="!tag.is_approved" class="text-[10px] px-1.5 py-0.5 rounded-full"

                                      :style="isTagSelected(tag.id) ? 'background:rgba(255,255,255,0.25); color:white' : 'background:#FEF3C7; color:#D97706'">pending</span>

                            </div>

                            <div x-show="isTagSelected(tag.id)">

                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>

                            </div>

                            <div x-show="!isTagSelected(tag.id) && tag.usage_count > 0">

                                <span class="text-xs opacity-50" x-text="tag.usage_count + 'x'"></span>

                            </div>

                        </button>

                    </template>



                    <p x-show="!tagSearchLoading && modalTagList.length === 0 && tagSearch.length > 0"

                       class="text-center py-4 text-sm" style="color: var(--color-text-secondary);">

                        {{ $isEnglish ? 'Tag not found. Create a new tag above.' : 'Tag tidak ditemukan. Buat tag baru di atas.' }}

                    </p>

                </div>



                {{-- Modal footer --}}

                <div class="p-4 border-t border-gray-100">

                    <button type="button" @click="showTagModal = false"

                            class="btn-primary-savora w-full py-3 rounded-2xl">

                        {{ $isEnglish ? 'Done' : 'Selesai' }}

                    </button>

                </div>

            </div>

        </div>

    </div>



    <script>

    const DRAFT_KEY = 'savora_create_draft_v2';

    const POPULAR_TAGS = @json($popularTags ?? []);



    function recipeForm() {

        return {

            // ── State ──

            imagePreview: null,

            imageFile: null,

            videoFileName: null,

            videoFile: null,

            newIngredient: '',

            newStep: '',

            isSubmitting: false,

            lastSaved: null,

            saveTimer: null,



            // ── Draft (persisted to localStorage) ──

            draft: {

                title: '',

                description: '',

                cooking_time: '',

                servings: '',

                calories: '',

                difficulty: 'mudah',

                category_id: '',

                ingredients: [],

                steps: [],

                selectedTags: [],   // [{id, name}]

            },



            // ── Tag modal ──

            showTagModal: false,

            tagSearch: '',

            tagSearchLoading: false,

            modalTagList: [],

            tagSearchTimer: null,

            createTagLoading: false,



            // ─────────────────────────────

            // DRAFT

            // ─────────────────────────────

            initDraft() {

                if (!window.SavoraSettings?.autoSaveDrafts) {
                    localStorage.removeItem(DRAFT_KEY);
                    this.modalTagList = POPULAR_TAGS.map(t => ({ ...t }));
                    return;
                }

                try {

                    const saved = localStorage.getItem(DRAFT_KEY);

                    if (saved) {

                        const parsed = JSON.parse(saved);

                        if (parsed.title || parsed.ingredients?.length || parsed.steps?.length) {

                            if (confirm('Ada draft tersimpan. Lanjutkan draft sebelumnya?')) {

                                this.draft = { ...this.draft, ...parsed };

                                this.lastSaved = 'draft';

                            } else {

                                localStorage.removeItem(DRAFT_KEY);

                            }

                        }

                    }

                } catch(e) {}



                // Load popular tags into modal by default

                this.modalTagList = POPULAR_TAGS.map(t => ({ ...t }));

            },



            scheduleSave() {

                if (!window.SavoraSettings?.autoSaveDrafts) return;

                if (this.saveTimer) clearTimeout(this.saveTimer);

                this.saveTimer = setTimeout(() => this.saveDraft(), 1500);

            },



            saveDraft() {

                if (!window.SavoraSettings?.autoSaveDrafts) return;

                try {

                    localStorage.setItem(DRAFT_KEY, JSON.stringify(this.draft));

                    const now = new Date();

                    this.lastSaved = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');

                } catch(e) {}

            },



            clearDraft() {

                if (!confirm('{{ $isEnglish ? 'Delete saved draft?' : 'Hapus draft tersimpan?' }}')) return;

                localStorage.removeItem(DRAFT_KEY);

                this.draft = { title:'', description:'', cooking_time:'', servings:'', calories:'', difficulty:'mudah', category_id:'', ingredients:[], steps:[], selectedTags:[] };

                this.imagePreview = null;

                this.imageFile = null;

                this.videoFileName = null;

                this.videoFile = null;

                this.lastSaved = null;

            },



            // ─────────────────────────────

            // IMAGE / VIDEO

            // ─────────────────────────────

            handleImage(e) {

                const f = e.target.files[0];

                if (!f) return;

                this.imageFile = f;

                const r = new FileReader();

                r.onload = ev => { this.imagePreview = ev.target.result; };

                r.readAsDataURL(f);

            },



            pickVideo(e) {

                const f = e.target.files[0];

                if (!f) return;

                if (f.size > 50 * 1024 * 1024) { alert('Video terlalu besar! Maksimal 50MB'); return; }

                this.videoFile = f;

                this.videoFileName = f.name;

            },



            removeVideo() {

                this.videoFileName = null;

                this.videoFile = null;

                const inp = this.$refs.videoInput;

                if (inp) inp.value = '';

            },



            // ─────────────────────────────

            // INGREDIENTS & STEPS

            // ─────────────────────────────

            addIngredient() {

                const v = this.newIngredient.trim();

                if (!v) return;

                this.draft.ingredients.push(v);

                this.newIngredient = '';

                this.scheduleSave();

            },



            addStep() {

                const v = this.newStep.trim();

                if (!v) return;

                this.draft.steps.push(v);

                this.newStep = '';

                this.scheduleSave();

            },



            // ─────────────────────────────

            // TAGS — MAX 3

            // ─────────────────────────────

            isTagSelected(id) {

                return this.draft.selectedTags.some(t => t.id === id);

            },



            togglePopularTag(tag) {

                if (this.isTagSelected(tag.id)) {

                    this.draft.selectedTags = this.draft.selectedTags.filter(t => t.id !== tag.id);

                } else {

                    if (this.draft.selectedTags.length >= 3) return;

                    this.draft.selectedTags.push(tag);

                }

                this.scheduleSave();

            },



            removeTag(index) {

                this.draft.selectedTags.splice(index, 1);

                this.scheduleSave();

            },



            // ─────────────────────────────

            // TAG MODAL

            // ─────────────────────────────

            openTagModal() {

                this.tagSearch = '';

                this.modalTagList = POPULAR_TAGS.map(t => ({ ...t }));

                this.showTagModal = true;

            },



            searchTags() {

                if (this.tagSearchTimer) clearTimeout(this.tagSearchTimer);

                const q = this.tagSearch.trim();



                if (!q) {

                    this.modalTagList = POPULAR_TAGS.map(t => ({ ...t }));

                    return;

                }



                this.tagSearchLoading = true;

                this.tagSearchTimer = setTimeout(async () => {

                    try {

                        const res = await fetch(`/api/tags/search?q=${encodeURIComponent(q)}&limit=15`, {

                            headers: { 'Accept': 'application/json' }

                        });

                        const json = await res.json();

                        if (json.success) {

                            this.modalTagList = json.data ?? [];

                        }

                    } catch(e) {

                        this.modalTagList = [];

                    } finally {

                        this.tagSearchLoading = false;

                    }

                }, 350);

            },



            toggleModalTag(tag) {

                if (this.isTagSelected(tag.id)) {

                    this.draft.selectedTags = this.draft.selectedTags.filter(t => t.id !== tag.id);

                } else {

                    if (this.draft.selectedTags.length >= 3) return;

                    this.draft.selectedTags.push({ id: tag.id, name: tag.name });

                }

                this.scheduleSave();

            },



            async createAndSelectTag() {

                const name = this.tagSearch.trim();

                if (!name || this.draft.selectedTags.length >= 3) return;



                this.createTagLoading = true;

                try {

                    const res = await fetch('/api/tags', {

                        method: 'POST',

                        headers: {

                            'Content-Type': 'application/json',

                            'Accept': 'application/json',

                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',

                        },

                        body: JSON.stringify({ name }),

                    });

                    const json = await res.json();

                    if (json.success && json.data?.id) {

                        const newTag = { id: json.data.id, name: json.data.name };

                        if (!this.isTagSelected(newTag.id)) {

                            this.draft.selectedTags.push(newTag);

                            this.scheduleSave();

                        }

                        // Add to modal list

                        this.modalTagList.unshift({ ...newTag, is_approved: false, usage_count: 0 });

                        this.tagSearch = '';

                        this.modalTagList = POPULAR_TAGS.map(t => ({ ...t }));

                    } else {

                        alert(json.message ?? '{{ $isEnglish ? 'Failed to create tag' : 'Gagal membuat tag' }}');

                    }

                } catch(e) {

                    alert('Terjadi kesalahan. Coba lagi.');

                } finally {

                    this.createTagLoading = false;

                }

            },



            // ─────────────────────────────

            // SUBMIT

            // ─────────────────────────────

            handleSubmit(e) {

                if (!this.imagePreview) {

                    alert('{{ $isEnglish ? 'Choose a recipe image first' : 'Pilih gambar resep terlebih dahulu' }}');

                    e.preventDefault(); return;

                }

                if (this.draft.ingredients.length === 0) {

                    alert('{{ $isEnglish ? 'Add at least 1 ingredient' : 'Tambahkan minimal 1 bahan' }}');

                    e.preventDefault(); return;

                }

                if (this.draft.steps.length === 0) {

                    alert('{{ $isEnglish ? 'Add at least 1 step' : 'Tambahkan minimal 1 langkah' }}');

                    e.preventDefault(); return;

                }



                this.isSubmitting = true;



                const form = this.$refs.form;



                // Inject ingredients & steps as hidden inputs

                this.draft.ingredients.forEach((v, i) => {

                    const inp = document.createElement('input');

                    inp.type = 'hidden'; inp.name = `ingredients[${i}]`; inp.value = v;

                    form.appendChild(inp);

                });

                this.draft.steps.forEach((v, i) => {

                    const inp = document.createElement('input');

                    inp.type = 'hidden'; inp.name = `steps[${i}]`; inp.value = v;

                    form.appendChild(inp);

                });



                // Re-attach image file if picked (file inputs reset on re-render)

                if (this.imageFile && this.$refs.imageInput) {

                    try {

                        const dt = new DataTransfer();

                        dt.items.add(this.imageFile);

                        this.$refs.imageInput.files = dt.files;

                    } catch(err) {

                        // DataTransfer not supported on some mobile browsers — file input already has the file

                    }

                }



                // Clear draft on successful submit (page will redirect anyway)

                localStorage.removeItem(DRAFT_KEY);

            }

        }

    }

    </script>

</body>

</html>
