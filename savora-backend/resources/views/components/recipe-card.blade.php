@props([
    'recipe',
    'rating' => null,
    'currentUserId' => null,
])

<div 
    x-data="recipeCard(@json($recipe), '{{ $currentUserId }}')"
    @click="!isPressed && navigateToDetail()"
    class="bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105 active:scale-95"
>
    <!-- Image Container -->
    <div class="relative h-48 bg-gray-200 overflow-hidden group">
        @if (isset($recipe['image']))
            <img 
                src="{{ $recipe['image'] }}" 
                alt="{{ $recipe['name'] ?? 'Recipe' }}"
                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
            >
        @else
            <div class="w-full h-full bg-linear-to-br from-orange-300 to-red-400 flex items-center justify-center">
                <svg class="w-16 h-16 text-white opacity-50" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M2 3h20v2H2V3zm1 4h3v14a2 2 0 002 2h10a2 2 0 002-2V7h3V5H3v2zm4 0v12h10V7H7zm4 2v8h2V9h-2zm4 0v8h2V9h-2z" />
                </svg>
            </div>
        @endif

        <!-- Overlay Info -->
        <div class="absolute inset-0 bg-linear-to-t from-black via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-4">
            @if (isset($recipe['category']))
                <span class="inline-block bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full w-fit">
                    {{ ucfirst($recipe['category']) }}
                </span>
            @endif
        </div>

        <!-- Favorite Button -->
        <button 
            @click.stop="toggleFavorite()"
            :disabled="isCheckingFavorite"
            class="absolute top-3 right-3 p-2 rounded-full bg-white shadow-md hover:shadow-lg transition-all transform hover:scale-110 active:scale-95"
        >
            <svg 
                class="w-6 h-6 transition-colors duration-200"
                :fill="isFavorite ? 'currentColor' : 'none'"
                :class="isFavorite ? 'text-red-500' : 'text-gray-400'"
                stroke="currentColor" 
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
        </button>

        <!-- Rating Badge -->
        @if ($rating)
            <div class="absolute bottom-3 left-3 bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                {{ number_format($rating, 1) }}
            </div>
        @endif
    </div>

    <!-- Content -->
    <div class="p-4">
        <!-- Title -->
        <h3 class="font-bold text-lg text-gray-900 mb-2 line-clamp-2">
            {{ $recipe['name'] ?? 'Unnamed Recipe' }}
        </h3>

        <!-- Description -->
        @if (isset($recipe['description']))
            <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                {{ $recipe['description'] }}
            </p>
        @endif

        <!-- Meta Info -->
        <div class="flex gap-4 text-sm text-gray-600 mb-4">
            @if (isset($recipe['prep_time']))
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00-.447.894l1.447 1.447 1-1V6z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ $recipe['prep_time'] }} min</span>
                </div>
            @endif

            @if (isset($recipe['servings']))
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM9 0a9 9 0 118 8.906 1 1 0 00-1.053-.97 6 6 0 10-9.953 4.97A1 1 0 009 0z" />
                    </svg>
                    <span>{{ $recipe['servings'] }} porsi</span>
                </div>
            @endif

            @if (isset($recipe['difficulty']))
                <div class="flex items-center gap-1">
                    <span class="text-xs px-2 py-0.5 rounded-full" 
                        :class="getDifficultyColor('{{ $recipe['difficulty'] }}')">
                        {{ ucfirst($recipe['difficulty'] ?? 'Medium') }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Author -->
        @if (isset($recipe['author']))
            <div class="flex items-center gap-2 pt-3 border-t border-gray-200">
                @if (isset($recipe['author']['avatar']))
                    <img 
                        src="{{ $recipe['author']['avatar'] }}" 
                        alt="{{ $recipe['author']['name'] }}"
                        class="w-8 h-8 rounded-full"
                    >
                @else
                    <div class="w-8 h-8 rounded-full bg-orange-200 flex items-center justify-center text-orange-600 text-xs font-bold">
                        {{ substr($recipe['author']['name'] ?? 'U', 0, 1) }}
                    </div>
                @endif
                <span class="text-sm text-gray-700 font-medium">
                    {{ $recipe['author']['name'] ?? 'Unknown' }}
                </span>
            </div>
        @endif
    </div>
</div>

<script>
function recipeCard(recipe, userId) {
    return {
        isPressed: false,
        isFavorite: false,
        isCheckingFavorite: true,
        recipe: recipe,
        userId: userId,

        init() {
            this.checkIfFavorite();
        },

        async checkIfFavorite() {
            if (!this.userId) {
                this.isCheckingFavorite = false;
                return;
            }

            try {
                const response = await fetch(`/api/favorites/check/${this.recipe.id}`, {
                    headers: {
                        'Authorization': `Bearer {{ auth()->user()?->api_token }}`,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.isFavorite = data.isFavorite || false;
                }
            } catch (error) {
                console.error('Error checking favorite:', error);
            } finally {
                this.isCheckingFavorite = false;
            }
        },

        async toggleFavorite() {
            if (!this.userId) {
                alert('Silakan login terlebih dahulu');
                return;
            }

            try {
                const method = this.isFavorite ? 'DELETE' : 'POST';
                const response = await fetch(`/api/favorites/${this.recipe.id}`, {
                    method: method,
                    headers: {
                        'Authorization': `Bearer {{ auth()->user()?->api_token }}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    this.isFavorite = !this.isFavorite;
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
            }
        },

        navigateToDetail() {
            window.location.href = `/recipes/${this.recipe.id}`;
        },

        getDifficultyColor(difficulty) {
            const colors = {
                'easy': 'bg-green-100 text-green-700',
                'medium': 'bg-yellow-100 text-yellow-700',
                'hard': 'bg-red-100 text-red-700'
            };
            return colors[difficulty?.toLowerCase()] || 'bg-gray-100 text-gray-700';
        }
    }
}
</script>
