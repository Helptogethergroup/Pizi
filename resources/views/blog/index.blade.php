@extends('layouts.app')
@section('title', 'Blog — PG Living Tips & Guides | Pizi')
@section('meta_description', 'Tips, guides, and stories about PG living, finding the right room, and city moving guides.')
@section('content')
<section class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
    <span class="text-xs font-semibold text-coral-600 uppercase tracking-wider">Pizi Journal</span>
    <h1 class="font-display font-black text-5xl md:text-6xl mt-3">Stories from the city.</h1>
    <p class="text-lg text-ink-900/70 mt-3 max-w-2xl">PG hunting tips, locality guides, moving checklists &amp; more.</p>

    {{-- Search --}}
    <form method="GET" class="mt-8 max-w-md">
        <div class="relative">
            <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Search articles..."
                   class="w-full pl-11 pr-4 py-3 rounded-xl border border-ink-900/15 focus:outline-none focus:border-coral-500 text-sm">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-ink-900/40">🔍</span>
        </div>
    </form>

    <div class="grid lg:grid-cols-3 gap-10 mt-10">
        <div class="lg:col-span-2">

            {{-- Featured hero post --}}
            @if($featured ?? null)
            <a href="{{ route('blog.show', $featured->slug) }}" class="group block mb-10 pz-tilt-card">
                <div class="aspect-[16/9] bg-ink-900/5 rounded-3xl overflow-hidden">
                    <img src="{{ $featured->cover_image_url ?: 'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=1200&q=80' }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500" alt="{{ $featured->title }}" loading="lazy">
                </div>
                <span class="inline-block mt-4 px-2.5 py-1 rounded-full bg-coral-50 text-coral-700 text-xs font-bold uppercase tracking-wider">Latest</span>
                <h2 class="font-display font-black text-3xl md:text-4xl mt-3 group-hover:text-coral-600 transition leading-tight">{{ $featured->title }}</h2>
                <p class="text-ink-900/60 mt-2 line-clamp-2">{{ $featured->excerpt }}</p>
                <p class="text-xs text-ink-900/40 mt-3">{{ $featured->published_at?->format('M d, Y') }} · {{ $featured->reading_time }} min read</p>
            </a>
            <hr class="border-ink-900/8 mb-10">
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                @forelse($blogs as $blog)
                    <a href="{{ route('blog.show', $blog->slug) }}" class="group block pz-tilt-card">
                        <div class="aspect-[4/3] bg-ink-900/5 rounded-2xl overflow-hidden mb-4">
                            <img src="{{ $blog->cover_image_url ?: 'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=800&q=80' }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition" alt="{{ $blog->title }}" loading="lazy">
                        </div>
                        <h3 class="font-display font-bold text-xl group-hover:text-coral-600 leading-tight">{{ $blog->title }}</h3>
                        <p class="text-ink-900/60 mt-2 line-clamp-2 text-sm">{{ $blog->excerpt }}</p>
                        <p class="text-xs text-ink-900/40 mt-3">{{ $blog->published_at?->format('M d, Y') }} · {{ $blog->reading_time }} min read</p>
                    </a>
                @empty
                    <p class="col-span-2 text-center text-ink-900/60 py-20">
                        @if($search ?? null)
                            No articles found for "<strong>{{ $search }}</strong>".
                        @else
                            Articles coming soon.
                        @endif
                    </p>
                @endforelse
            </div>
            <div class="mt-12">{{ $blogs->links() }}</div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-8">
            @if(($popular ?? collect())->count())
            <div class="bg-cream rounded-2xl border border-ink-900/8 p-6">
                <h3 class="font-display font-bold text-lg mb-4">🔥 Popular reads</h3>
                <div class="space-y-4">
                    @foreach($popular as $p)
                        <a href="{{ route('blog.show', $p->slug) }}" class="flex items-start gap-3 group">
                            <div class="w-16 h-16 rounded-lg overflow-hidden bg-ink-900/5 flex-shrink-0">
                                <img src="{{ $p->cover_image_url ?: 'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=200&q=80' }}" class="w-full h-full object-cover" alt="{{ $p->title }}" loading="lazy">
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-semibold text-sm leading-snug line-clamp-2 group-hover:text-coral-600 transition">{{ $p->title }}</h4>
                                <p class="text-xs text-ink-900/40 mt-1">{{ $p->reading_time }} min read</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="bg-ink-950 text-cream rounded-2xl p-6">
                <h3 class="font-display font-bold text-lg mb-2">📬 Get PG tips in your inbox</h3>
                <p class="text-sm text-cream/60 mb-4">Locality guides, safety checklists &amp; move-in tips — no spam.</p>
                @if(session('success'))
                    <p class="text-xs text-emerald-400 font-semibold mb-2">✓ {{ session('success') }}</p>
                @endif
                <form method="POST" action="{{ route('newsletter.subscribe') }}" class="space-y-2">
                    @csrf
                    <input type="email" required placeholder="you@example.com" name="email"
                           class="w-full px-4 py-2.5 rounded-xl border border-cream/20 bg-white/5 text-cream placeholder:text-cream/40 text-sm focus:outline-none focus:border-coral-400">
                    <button type="submit" class="w-full py-2.5 bg-coral-500 hover:bg-coral-600 rounded-xl font-bold text-sm transition">Subscribe</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-ink-900/8 p-6 text-center">
                <div class="text-3xl mb-2">🏠</div>
                <h3 class="font-display font-bold text-lg">Looking for a PG?</h3>
                <p class="text-sm text-ink-900/60 mt-1 mb-4">Browse verified listings across Delhi NCR.</p>
                <a href="{{ route('search') }}" class="inline-block w-full py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm transition">Browse PGs →</a>
            </div>
        </aside>
    </div>
</section>
@endsection
