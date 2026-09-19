@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-cream pt-8 pb-16">
    <!-- Header with University Info -->
    <div class="bg-white border-b border-ink/10">
        <div class="max-w-6xl mx-auto px-4 py-8">
            <a href="{{ route('universities.index') }}" class="inline-flex items-center gap-2 text-coral-500 hover:text-coral-600 mb-4">
                ← Back to Universities
            </a>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-start">
                <div class="md:col-span-2">
                    <h1 class="text-4xl font-bold text-ink mb-2">{{ $university->name }}</h1>
                    <p class="text-lg text-ink/60 mb-4">{{ $university->abbreviation }}</p>
                    <div class="space-y-2 text-ink/70">
                        @if($university->address)
                            <p><strong><i class="fa-solid fa-location-dot fa-fw"></i> Address:</strong> {{ $university->address }}</p>
                        @endif
                        <p><strong><i class="fa-solid fa-city fa-fw"></i> City:</strong> {{ ucfirst($university->city) }}</p>
                        <p><strong><i class="fa-solid fa-book fa-fw"></i> Type:</strong> {{ ucfirst($university->type) }}</p>
                        <p><strong><i class="fa-solid fa-location-dot fa-fw"></i> Coordinates:</strong> {{ $university->latitude }}, {{ $university->longitude }}</p>
                    </div>
                </div>
                <div class="bg-coral-50 rounded-lg p-6 border border-coral-100">
                    <p class="text-sm text-ink/60 mb-2">PGs NEARBY (3KM RADIUS)</p>
                    <p class="text-4xl font-bold text-coral-500">{{ $properties->count() }}</p>
                    <p class="text-xs text-ink/50 mt-2">showing {{ $properties->count() }} results</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <form id="filterForm" class="bg-white rounded-lg shadow-sm p-6 border border-ink/5 mb-8">
            <h3 class="font-bold text-ink mb-4"><i class="fa-solid fa-magnifying-glass fa-fw"></i> Refine Results</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Budget Min -->
                <div>
                    <label class="block text-xs font-semibold text-ink/70 mb-2">MIN BUDGET</label>
                    <input type="number" name="min_budget" placeholder="0" min="0" value="{{ $minBudget }}"
                           class="w-full px-3 py-2 text-sm border border-ink/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-400">
                </div>

                <!-- Budget Max -->
                <div>
                    <label class="block text-xs font-semibold text-ink/70 mb-2">MAX BUDGET</label>
                    <input type="number" name="max_budget" placeholder="50000" min="1000" value="{{ $maxBudget }}"
                           class="w-full px-3 py-2 text-sm border border-ink/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-400">
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-xs font-semibold text-ink/70 mb-2">GENDER</label>
                    <select name="gender" class="w-full px-3 py-2 text-sm border border-ink/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-coral-400">
                        <option value="">All</option>
                        <option value="male" {{ $gender === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ $gender === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="unisex" {{ $gender === 'unisex' ? 'selected' : '' }}>Unisex</option>
                    </select>
                </div>

                <!-- Filter Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-coral-500 hover:bg-coral-600 text-white font-bold py-2 px-4 rounded-lg transition text-sm">
                        Filter
                    </button>
                </div>

                <!-- Clear Button -->
                <div class="flex items-end">
                    <a href="{{ route('universities.show', $university->id) }}" class="w-full bg-ink/10 hover:bg-ink/20 text-ink font-bold py-2 px-4 rounded-lg transition text-sm text-center">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

   <!-- Properties Grid -->
    <div class="max-w-6xl mx-auto px-4">
        @if($properties->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($properties as $prop)
                    <a href="{{ route('property.show', $prop->slug) }}" class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition border border-ink/5 overflow-hidden group h-full flex flex-col">
                        
                        <!-- Image -->
                        <div class="bg-gradient-to-br from-coral-100 to-coral-50 h-48 relative overflow-hidden">
                            @if($prop->cover_image)
                                <img src="{{ asset('storage/' . $prop->cover_image) }}" 
                                     alt="{{ $prop->name }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-5xl"><i class="fa-solid fa-house fa-fw"></i></div>
                            @endif
                            
                            @if($prop->distance_km)
                            <!-- Distance Badge -->
                            <div class="absolute top-3 right-3 bg-coral-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                                {{ $prop->distance_km }} km away
                            </div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="p-5 flex-1 flex flex-col">
                            <h3 class="font-bold text-lg text-ink group-hover:text-coral-500 transition mb-1">{{ $prop->name }}</h3>
                            <p class="text-xs text-ink/50 mb-3"><i class="fa-solid fa-location-dot fa-fw"></i> {{ $prop->locality_name }}, {{ $prop->city_name }}</p>
                            
                            <!-- Details -->
                            <div class="space-y-2 text-sm mb-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-ink/60">Rent Range</span>
                                    <span class="font-bold text-coral-500">₹{{ number_format($prop->rent_min) }} - ₹{{ number_format($prop->rent_max) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-ink/60">Gender</span>
                                    <span class="font-medium text-ink capitalize">{{ $prop->gender }}</span>
                                </div>
                                @if($prop->owner_name)
                                    <div class="flex items-center justify-between">
                                        <span class="text-ink/60">Owner</span>
                                        <span class="font-medium text-ink">{{ Str::limit($prop->owner_name, 15) }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Action Button -->
                            <div class="w-full bg-coral-500 group-hover:bg-coral-600 text-white font-bold py-2.5 px-3 rounded-xl transition text-sm text-center">
                                View Details & Inquire →
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border border-ink/5 p-12 text-center">
                <p class="text-2xl mb-2"><i class="fa-solid fa-magnifying-glass fa-fw"></i></p>
                <p class="text-lg font-semibold text-ink mb-2">No PGs Found</p>
                <p class="text-ink/60 mb-6">Try adjusting your budget or gender preferences</p>
                <a href="{{ route('universities.index') }}" class="inline-block bg-coral-500 text-white font-bold py-2 px-6 rounded-lg hover:bg-coral-600 transition">
                    Browse Other Universities
                </a>
            </div>
        @endif
    </div>

    <!-- Contact Section -->
    <div class="max-w-6xl mx-auto px-4 mt-16">
        <div class="bg-gradient-to-r from-ink to-ink/80 text-white rounded-lg p-8">
            <h3 class="text-2xl font-bold mb-4">Interested? Let's Connect!</h3>
            <p class="text-white/80 mb-6">Our team will help you find the perfect PG near {{ $university->abbreviation }}. Call us today!</p>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="https://wa.me/918006680092?text=Hi%2C%20I%27m%20looking%20for%20a%20PG%20near%20{{ urlencode($university->name) }}" 
                   target="_blank"
                   class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition inline-flex items-center justify-center gap-2">
                    <i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp: 8006680092
                </a>
                <a href="tel:8006680092" class="bg-white/20 hover:bg-white/30 text-white font-bold py-3 px-6 rounded-lg transition inline-flex items-center justify-center gap-2 border border-white/50">
                    <i class="fa-solid fa-phone fa-fw"></i> Call: 8006680092
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function inquire(propertyId, propertyName) {
    const message = `Hi, I'm interested in the PG: ${propertyName}. Can you tell me more?`;
    const encoded = encodeURIComponent(message);
    window.location.href = `https://wa.me/918006680092?text=${encoded}`;
}
</script>
@endsection
