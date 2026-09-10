{{--
    ========================================================================
    PIZI REVIEWS SECTION  (Step 10 + Step 14 SEO schema)  — Pizi theme
    Property detail page me LOCATION section ke THEEK UPAR paste:

        @include('partials.reviews-section', ['property' => $property])

    Self-contained: khud approved reviews fetch karta hai.
    ========================================================================
--}}

@php
    use App\Models\Review;

    $reviews = Review::where('property_id', $property->id)
        ->where('status', 'approved')
        ->orderByDesc('created_at')
        ->get();

    $total = $reviews->count();
    $avg   = $total ? round($reviews->avg('rating'), 1) : 0;

    $breakdown = [];
    for ($s = 5; $s >= 1; $s--) {
        $c = $reviews->where('rating', $s)->count();
        $breakdown[$s] = ['count' => $c, 'pct' => $total ? round($c / $total * 100) : 0];
    }

    $stars = function ($val) {
        $val = (int) round($val);
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $out .= '<span class="' . ($i <= $val ? 'text-amber-400' : 'text-ink-200') . '">&#9733;</span>';
        }
        return $out;
    };
@endphp

<section id="reviews" class="py-8 lg:py-12 bg-cream scroll-mt-24">
    <div class="max-w-7xl mx-auto px-4 lg:px-8">

        <h2 class="font-display font-black text-2xl lg:text-3xl text-ink-950 mb-6">⭐ Reviews &amp; Ratings</h2>

        {{-- ===== FLASH ===== --}}
        @if (session('review_success'))
            <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold">
                {{ session('review_success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm font-semibold">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ===== AGGREGATE HEADER ===== --}}
        <div class="grid md:grid-cols-3 gap-6 bg-white rounded-2xl border border-ink-900/10 p-6 lg:p-8">
            <div class="flex flex-col items-center justify-center text-center md:border-r border-ink-900/10">
                <div class="font-display font-black text-5xl text-ink-950">{{ number_format($avg, 1) }}</div>
                <div class="text-2xl leading-none mt-1">{!! $stars($avg) !!}</div>
                <div class="text-sm text-ink-900/50 mt-2 font-semibold">{{ $total }} {{ \Illuminate\Support\Str::plural('review', $total) }}</div>
            </div>
            <div class="md:col-span-2 space-y-2 self-center">
                @foreach ($breakdown as $star => $b)
                    <div class="flex items-center gap-3">
                        <span class="w-10 text-sm text-ink-700 font-semibold">{{ $star }} &#9733;</span>
                        <div class="flex-1 h-2.5 bg-ink-100 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-400 rounded-full" style="width: {{ $b['pct'] }}%"></div>
                        </div>
                        <span class="w-10 text-right text-sm text-ink-900/50">{{ $b['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== FILTER + WRITE BTN ===== --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2" id="reviewFilterBar">
                <button type="button" data-filter="all"
                    class="filter-btn px-3 py-1.5 rounded-full text-sm font-bold bg-ink-950 text-cream">All</button>
                @for ($s = 5; $s >= 1; $s--)
                    <button type="button" data-filter="{{ $s }}"
                        class="filter-btn px-3 py-1.5 rounded-full text-sm font-bold bg-white border border-ink-900/10 text-ink-700 hover:border-coral-500">
                        {{ $s }} &#9733;
                    </button>
                @endfor
            </div>
            <button type="button" onclick="document.getElementById('writeReviewBox').classList.toggle('hidden')"
                class="px-5 py-2.5 rounded-xl bg-coral-500 hover:bg-coral-600 text-white text-sm font-bold transition shadow-lg shadow-coral-500/30">
                ✍️ Write a Review
            </button>
        </div>

        {{-- ===== WRITE REVIEW FORM ===== --}}
        <div id="writeReviewBox" class="hidden mt-5 bg-white rounded-2xl border border-ink-900/10 p-6 lg:p-8">
            <h3 class="font-display font-bold text-lg text-ink-950 mb-4">Share your experience</h3>
            <form method="POST" action="{{ route('reviews.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="property_id" value="{{ $property->id }}">

                <div>
                    <label class="block text-sm font-bold text-ink-700 mb-1">Overall rating *</label>
                    <div class="star-picker text-3xl text-ink-200 select-none" data-target="ratingInput">
                        @for ($i = 1; $i <= 5; $i++)<span data-v="{{ $i }}" class="cursor-pointer">&#9733;</span>@endfor
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="" required>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    @foreach (['cleanliness' => 'Cleanliness', 'food' => 'Food', 'staff' => 'Staff', 'value_for_money' => 'Value for Money', 'amenities' => 'Amenities'] as $key => $label)
                        <div>
                            <label class="block text-sm text-ink-700 mb-1 font-semibold">{{ $label }}</label>
                            <div class="star-picker text-xl text-ink-200 select-none" data-target="{{ $key }}Input">
                                @for ($i = 1; $i <= 5; $i++)<span data-v="{{ $i }}" class="cursor-pointer">&#9733;</span>@endfor
                            </div>
                            <input type="hidden" name="{{ $key }}" id="{{ $key }}Input" value="">
                        </div>
                    @endforeach
                </div>

                @guest
                    <div class="grid sm:grid-cols-2 gap-4">
                        <input type="text" name="reviewer_name" placeholder="Your name *" required
                            class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm">
                        <input type="text" name="reviewer_phone" placeholder="Phone (optional)"
                            class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm">
                    </div>
                @endguest

                <input type="text" name="title" placeholder="Title (e.g. Great PG near metro)"
                    class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm">

                <textarea name="comment" rows="4" placeholder="Tell others about your stay... *" required minlength="10"
                    class="w-full px-4 py-3 rounded-xl border border-ink-900/15 focus:border-coral-500 outline-none text-sm resize-none"></textarea>

                <button type="submit"
                    class="px-6 py-3 rounded-xl bg-coral-500 hover:bg-coral-600 text-white text-sm font-bold transition shadow-lg shadow-coral-500/30">
                    Submit Review
                </button>
            </form>
        </div>

        {{-- ===== LIST ===== --}}
        <div class="mt-8 space-y-5" id="reviewList">
            @forelse ($reviews as $r)
                <article class="review-item bg-white rounded-2xl border border-ink-900/10 p-5 lg:p-6" data-rating="{{ $r->rating }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-ink-950">{{ $r->reviewer_name ?: 'Guest' }}</span>
                                @if ($r->is_verified_tenant)
                                    <span class="text-[11px] font-bold bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full">✓ Verified Tenant</span>
                                @endif
                            </div>
                            <div class="text-lg leading-none mt-1">{!! $stars($r->rating) !!}</div>
                        </div>
                        <span class="text-xs text-ink-900/40 whitespace-nowrap">{{ $r->created_at?->format('d M Y') }}</span>
                    </div>

                    @if ($r->title)<h4 class="font-display font-bold text-ink-950 mt-3">{{ $r->title }}</h4>@endif
                    <p class="text-ink-700 text-sm mt-1 leading-relaxed">{{ $r->comment }}</p>

                    @php
                        $subs = collect([
                            'Cleanliness' => $r->cleanliness, 'Food' => $r->food, 'Staff' => $r->staff,
                            'Value' => $r->value_for_money, 'Amenities' => $r->amenities,
                        ])->filter(fn ($v) => !is_null($v));
                    @endphp
                    @if ($subs->count())
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach ($subs as $label => $v)
                                <span class="text-xs bg-cream border border-ink-900/10 rounded-full px-2.5 py-1 text-ink-700 font-semibold">
                                    {{ $label }}: <span class="text-amber-500">{{ $v }}&#9733;</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($r->owner_response)
                        <div class="mt-4 ml-3 pl-4 border-l-2 border-coral-200 bg-coral-50/50 rounded-r-xl p-3">
                            <div class="text-xs font-bold text-coral-700">Response from owner</div>
                            <p class="text-sm text-ink-700 mt-1">{{ $r->owner_response }}</p>
                        </div>
                    @endif

                    <div class="flex items-center gap-4 mt-4 text-xs text-ink-900/50">
                        <form method="POST" action="{{ route('reviews.helpful', $r->id) }}">
                            @csrf <input type="hidden" name="type" value="yes">
                            <button class="hover:text-ink-950 font-semibold">👍 Helpful ({{ $r->helpful_count }})</button>
                        </form>
                        <form method="POST" action="{{ route('reviews.helpful', $r->id) }}">
                            @csrf <input type="hidden" name="type" value="no">
                            <button class="hover:text-ink-950 font-semibold">👎 ({{ $r->not_helpful_count }})</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="text-center text-ink-900/50 py-10 bg-white rounded-2xl border border-ink-900/10 font-semibold">
                    No reviews yet. Be the first to review! ✨
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- ===== STEP 14: SEO RICH SNIPPET ===== --}}
@if ($total > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LodgingBusiness",
  "name": @json($property->name ?? 'PG'),
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ $avg }}",
    "reviewCount": "{{ $total }}",
    "bestRating": "5",
    "worstRating": "1"
  },
  "review": [
    @foreach ($reviews->take(10) as $r)
    {
      "@type": "Review",
      "author": { "@type": "Person", "name": @json($r->reviewer_name ?: 'Guest') },
      "datePublished": "{{ $r->created_at?->format('Y-m-d') }}",
      "reviewRating": { "@type": "Rating", "ratingValue": "{{ $r->rating }}", "bestRating": "5", "worstRating": "1" },
      "reviewBody": @json(\Illuminate\Support\Str::limit($r->comment, 300))
    }@if (!$loop->last),@endif
    @endforeach
  ]
}
</script>
@endif

{{-- ===== JS: star picker + filter ===== --}}
<script>
(function () {
    document.querySelectorAll('.star-picker').forEach(function (picker) {
        var input = document.getElementById(picker.dataset.target);
        var stars = picker.querySelectorAll('span[data-v]');
        function paint(v) {
            stars.forEach(function (s) {
                s.classList.toggle('text-amber-400', parseInt(s.dataset.v) <= v);
                s.classList.toggle('text-ink-200', parseInt(s.dataset.v) > v);
            });
        }
        stars.forEach(function (s) {
            s.addEventListener('mouseenter', function () { paint(parseInt(s.dataset.v)); });
            s.addEventListener('click', function () { input.value = s.dataset.v; paint(parseInt(s.dataset.v)); });
        });
        picker.addEventListener('mouseleave', function () { paint(parseInt(input.value || 0)); });
    });

    var bar = document.getElementById('reviewFilterBar');
    if (bar) {
        bar.querySelectorAll('.filter-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                bar.querySelectorAll('.filter-btn').forEach(function (b) {
                    b.classList.remove('bg-ink-950', 'text-cream');
                    b.classList.add('bg-white', 'text-ink-700', 'border', 'border-ink-900/10');
                });
                btn.classList.add('bg-ink-950', 'text-cream');
                btn.classList.remove('bg-white', 'text-ink-700');
                var f = btn.dataset.filter;
                document.querySelectorAll('#reviewList .review-item').forEach(function (item) {
                    item.style.display = (f === 'all' || item.dataset.rating === f) ? '' : 'none';
                });
            });
        });
    }
})();
</script>