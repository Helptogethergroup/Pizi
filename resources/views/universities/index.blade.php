@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-cream via-white to-cream">



    {{-- ===== HERO SECTION ===== --}}
    
    <div class="relative overflow-hidden pt-16 pb-20 px-4">
        <!-- Animated background elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-coral-500/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-20 -left-40 w-80 h-80 bg-ink-900/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <div class="relative max-w-6xl mx-auto text-center">
            <div class="mb-6 inline-block">
                <span class="px-4 py-2 bg-coral-100 text-coral-700 rounded-full text-sm font-bold">
                    <i class="fa-solid fa-graduation-cap fa-fw"></i> FIND YOUR HOME NEAR TOP UNIVERSITIES
                </span>
            </div>

            <h1 class="font-display font-black text-5xl lg:text-7xl text-ink mb-6 leading-tight">
                PGs Near Delhi's
                <span class="bg-gradient-to-r from-coral-500 via-coral-400 to-coral-600 bg-clip-text text-transparent">
                    Top Institutions
                </span>
            </h1>

            <p class="text-lg lg:text-xl text-ink/70 max-w-2xl mx-auto mb-12 leading-relaxed">
                Discover verified, affordable PGs, hostels & co-living spaces within walking distance of India's finest universities and colleges
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row justify-center gap-4 mb-12">
                <a href="#universities" class="px-8 py-4 bg-gradient-to-r from-coral-500 to-coral-600 text-white rounded-2xl font-bold shadow-lg shadow-coral-500/30 hover:shadow-xl hover:shadow-coral-500/50 transition-all hover:scale-105 inline-flex items-center justify-center gap-2">
                    ↓ Browse Universities
                </a>
                <a href="{{ route('search') }}" class="px-8 py-4 bg-white border-2 border-ink/20 text-ink rounded-2xl font-bold hover:border-coral-500 hover:bg-coral-50 transition-all inline-flex items-center justify-center gap-2">
                    <i class="fa-solid fa-magnifying-glass fa-fw"></i> Search All PGs
                </a>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-3 gap-4 max-w-xl mx-auto">
                <div class="bg-white/60 backdrop-blur-sm border border-ink/10 rounded-2xl p-4">
                    <div class="text-3xl font-bold text-coral-500">30+</div>
                    <div class="text-xs text-ink/60 font-semibold">Universities</div>
                </div>
                <div class="bg-white/60 backdrop-blur-sm border border-ink/10 rounded-2xl p-4">
                    <div class="text-3xl font-bold text-ink">1000+</div>
                    <div class="text-xs text-ink/60 font-semibold">PGs Listed</div>
                </div>
                <div class="bg-white/60 backdrop-blur-sm border border-ink/10 rounded-2xl p-4">
                    <div class="text-3xl font-bold text-emerald-500">0%</div>
                    <div class="text-xs text-ink/60 font-semibold">Brokerage</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== FILTER SECTION ===== --}}
    <div class="max-w-6xl mx-auto px-4 -mt-8 relative z-10">
        <div class="bg-white rounded-3xl shadow-2xl shadow-ink/10 border border-ink/5 p-8 backdrop-blur-sm">
            <h3 class="font-bold text-ink text-lg mb-6"><i class="fa-solid fa-magnifying-glass fa-fw"></i> Smart Filter</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Min Budget -->
                <div>
                    <label class="block text-xs font-bold text-ink/60 mb-2 uppercase">Min Budget</label>
                    <input type="number" id="minBudget" placeholder="5000" min="0" max="100000"
                           class="w-full px-4 py-3 border-2 border-ink/10 rounded-xl focus:outline-none focus:border-coral-500 focus:ring-2 focus:ring-coral-100 transition" value="0">
                </div>

                <!-- Max Budget -->
                <div>
                    <label class="block text-xs font-bold text-ink/60 mb-2 uppercase">Max Budget</label>
                    <input type="number" id="maxBudget" placeholder="50000" min="1000" max="100000"
                           class="w-full px-4 py-3 border-2 border-ink/10 rounded-xl focus:outline-none focus:border-coral-500 focus:ring-2 focus:ring-coral-100 transition" value="50000">
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-xs font-bold text-ink/60 mb-2 uppercase">Gender</label>
                    <select id="gender" class="w-full px-4 py-3 border-2 border-ink/10 rounded-xl focus:outline-none focus:border-coral-500 focus:ring-2 focus:ring-coral-100 transition">
                        <option value="">All</option>
                        <option value="male">Boys</option>
                        <option value="female">Girls</option>
                        <option value="unisex">Unisex</option>
                    </select>
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-xs font-bold text-ink/60 mb-2 uppercase">Type</label>
                    <select id="type" class="w-full px-4 py-3 border-2 border-ink/10 rounded-xl focus:outline-none focus:border-coral-500 focus:ring-2 focus:ring-coral-100 transition">
                        <option value="">All</option>
                        <option value="pg">PG</option>
                        <option value="hostel">Hostel</option>
                        <option value="coliving">Co-living</option>
                    </select>
                </div>

                <!-- Button -->
                <div class="flex items-end">
                    <button id="applyFilter" onclick="applyFilters()"
                            class="w-full bg-gradient-to-r from-coral-500 to-coral-600 hover:from-coral-600 hover:to-coral-700 text-white font-bold py-3 px-4 rounded-xl transition-all hover:shadow-lg shadow-coral-500/30 flex items-center justify-center gap-2">
                        <span>Apply</span>
                        <i class="fas fa-filter text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== UNIVERSITIES GRID ===== --}}
    <div class="max-w-6xl mx-auto px-4 py-20" id="universities">
        <div class="text-center mb-16">
            <h2 class="font-display font-black text-4xl text-ink mb-3">Top Institutions</h2>
            <p class="text-ink/60 text-lg">Choose a university to see all nearby PGs</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($universities as $uni)
                <a href="{{ route('universities.show', $uni->id) }}"
                   class="group relative bg-white rounded-2xl border border-ink/10 overflow-hidden hover:shadow-2xl hover:shadow-coral-500/20 transition-all duration-300 hover:-translate-y-2">

                    {{-- Card Background Gradient --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-coral-500/5 to-ink-900/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                    {{-- Icon Background --}}
                    <div class="absolute -top-20 -right-20 w-40 h-40 bg-coral-500/10 rounded-full blur-2xl group-hover:bg-coral-500/20 transition-all"></div>

                    {{-- Content --}}
                    <div class="relative p-6">
                        {{-- Icon + Header --}}
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="font-display font-bold text-xl text-ink group-hover:text-coral-500 transition mb-1 leading-tight">
                                    {{ Str::limit($uni->name, 35) }}
                                </h3>
                                <p class="text-sm text-ink/60 font-semibold">{{ $uni->abbreviation }}</p>
                            </div>
                            <div class="text-4xl opacity-60 group-hover:opacity-100 transition-transform group-hover:scale-110">
                                @if($uni->type === 'university')
                                    <i class="fa-solid fa-graduation-cap fa-fw"></i>
                                @else
                                    <i class="fa-solid fa-school fa-fw"></i>
                                @endif
                            </div>
                        </div>

                        {{-- Info Grid --}}
                        <div class="space-y-2 text-sm mb-6 pb-6 border-b border-ink/10">
                            <div class="flex items-center gap-2 text-ink/70">
                                <span class="text-xs"><i class="fa-solid fa-location-dot fa-fw"></i></span>
                                <span><strong>{{ ucfirst($uni->city) }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2 text-ink/70">
                                <span class="text-xs"><i class="fa-solid fa-book fa-fw"></i></span>
                                <span><strong>{{ ucfirst($uni->type) }}</strong></span>
                            </div>
                            @if($uni->address)
                                <div class="flex items-start gap-2 text-ink/60 text-xs leading-snug">
                                    <span class="mt-0.5"><i class="fa-solid fa-thumbtack fa-fw"></i></span>
                                    <span>{{ Str::limit($uni->address, 50) }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- PG Count Badge --}}
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-ink/50 font-semibold">Nearby PGs</span>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl font-black text-coral-500">{{ $uni->property_count ?? 0 }}</span>
                                <span class="text-xs text-coral-500 font-bold">within 3km</span>
                            </div>
                        </div>

                        {{-- Hover CTA --}}
                        <div class="mt-4 opacity-0 group-hover:opacity-100 transition-opacity">
                            <div class="py-3 px-4 bg-gradient-to-r from-coral-50 to-coral-100 border border-coral-200 rounded-xl text-center">
                                <span class="text-coral-700 font-bold text-sm">View PGs →</span>
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-full text-center py-20">
                    <p class="text-ink/60 text-lg">No universities found</p>
                </div>
            @endforelse
        </div>
    </div>
    
    

    {{-- ===== INFO SECTION ===== --}}
    <div class="bg-gradient-to-r from-ink-900 via-ink-800 to-ink-900 text-white py-20 px-4 relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-coral-500 rounded-full blur-3xl"></div>
        </div>

        <div class="relative max-w-6xl mx-auto">
            <h2 class="font-display font-black text-4xl mb-12 text-center">How It Works</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 rounded-2xl bg-coral-500/20 border border-coral-500/50 flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl"><i class="fa-solid fa-graduation-cap fa-fw"></i></span>
                    </div>
                    <h3 class="font-bold text-xl mb-2">Pick Your University</h3>
                    <p class="text-white/70 text-sm leading-relaxed">Choose from 30+ top institutions in Delhi NCR</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 rounded-2xl bg-coral-500/20 border border-coral-500/50 flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl"><i class="fa-solid fa-house fa-fw"></i></span>
                    </div>
                    <h3 class="font-bold text-xl mb-2">Browse Nearby PGs</h3>
                    <p class="text-white/70 text-sm leading-relaxed">See all verified PGs within 3km of your university</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 rounded-2xl bg-coral-500/20 border border-coral-500/50 flex items-center justify-center mx-auto mb-4">
                        <span class="text-3xl"><i class="fa-solid fa-phone fa-fw"></i></span>
                    </div>
                    <h3 class="font-bold text-xl mb-2">Schedule a Visit</h3>
                    <p class="text-white/70 text-sm leading-relaxed">Call us for free site visits with zero brokerage</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== CTA SECTION ===== --}}
    <div class="max-w-4xl mx-auto px-4 py-20">
        <div class="bg-gradient-to-r from-coral-500 via-coral-400 to-coral-600 rounded-3xl p-12 text-white text-center shadow-2xl shadow-coral-500/30">
            <h3 class="font-display font-black text-4xl mb-4">Ready to Move In?</h3>
            <p class="text-lg mb-8 text-white/90 max-w-2xl mx-auto leading-relaxed">
                Our team will help you find the perfect PG near your university. Call us now for a quick consultation!
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="https://wa.me/918006680092?text=Hi%20Pizi%2C%20I%27m%20looking%20for%20a%20PG%20near%20a%20university"
                   target="_blank" class="px-8 py-4 bg-white text-coral-600 rounded-2xl font-bold hover:bg-coral-50 transition-all hover:scale-105 inline-flex items-center justify-center gap-2">
                    <i class="fa-solid fa-comment-dots fa-fw"></i> WhatsApp: 8006680092
                </a>
                <a href="tel:8006680092" class="px-8 py-4 bg-white/20 border-2 border-white text-white rounded-2xl font-bold hover:bg-white/30 transition-all inline-flex items-center justify-center gap-2">
                    <i class="fa-solid fa-phone fa-fw"></i> Call: 8006680092
                </a>
            </div>
        </div>
    </div>

</div>

<style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    .animate-fadeInUp {
        animation: fadeInUp 0.6s ease-out forwards;
    }

    .animate-float {
        animation: float 3s ease-in-out infinite;
    }

    a[href*="universities.show"] {
        animation: fadeInUp 0.6s ease-out backwards;
    }

    a[href*="universities.show"]:nth-child(1) { animation-delay: 0.05s; }
    a[href*="universities.show"]:nth-child(2) { animation-delay: 0.1s; }
    a[href*="universities.show"]:nth-child(3) { animation-delay: 0.15s; }
    a[href*="universities.show"]:nth-child(4) { animation-delay: 0.2s; }
    a[href*="universities.show"]:nth-child(5) { animation-delay: 0.25s; }
    a[href*="universities.show"]:nth-child(6) { animation-delay: 0.3s; }
    a[href*="universities.show"]:nth-child(7) { animation-delay: 0.35s; }
    a[href*="universities.show"]:nth-child(8) { animation-delay: 0.4s; }
    a[href*="universities.show"]:nth-child(9) { animation-delay: 0.45s; }
    a[href*="universities.show"]:nth-child(10) { animation-delay: 0.5s; }
    a[href*="universities.show"]:nth-child(11) { animation-delay: 0.55s; }
    a[href*="universities.show"]:nth-child(12) { animation-delay: 0.6s; }
</style>

<script>
function applyFilters() {
    alert('Filters will apply to results');
}
</script>

@endsection