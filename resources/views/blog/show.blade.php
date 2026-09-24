@extends('layouts.app')
@section('title', ($blog->meta_title ?: $blog->title) . ' | Pizi')
@section('meta_description', $blog->meta_description ?: Str::limit(strip_tags($blog->excerpt ?? ''), 160))
@section('og_image', $blog->cover_image_url ?: '')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": @json($blog->title),
  "image": @json($blog->cover_image_url),
  "datePublished": @json($blog->published_at?->toIso8601String()),
  "author": { "@type": "Organization", "name": "Pizi" }
}
</script>
@endsection

@section('content')
<div class="max-w-7xl mx-auto px-4 lg:px-8 py-16">
    <a href="{{ route('blog.index') }}" class="text-sm text-coral-600 font-semibold hover:text-coral-700 transition">← Back to Blog</a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mt-6">
        <article class="lg:col-span-2 max-w-none">
            <span class="text-xs font-semibold text-coral-600 uppercase tracking-wider">{{ $blog->published_at?->format('M d, Y') }} · {{ $blog->reading_time }} min read · {{ number_format($blog->view_count) }} views</span>
            <h1 class="font-display font-black text-4xl md:text-5xl leading-[1.1] mt-3">{{ $blog->title }}</h1>

            @if($blog->cover_image_url)
                <img src="{{ $blog->cover_image_url }}" alt="{{ $blog->title }}" class="w-full aspect-[16/9] object-cover rounded-3xl mt-8">
            @endif

            <div class="prose prose-lg mt-10 text-ink-900/85 leading-relaxed max-w-none">
                {!! $blog->content !!}
            </div>

            {{-- Share --}}
            <div class="mt-10 pt-6 border-t border-ink-900/10 flex items-center gap-3">
                <span class="text-sm font-bold text-ink-900/70">Share:</span>
                <a href="https://wa.me/?text={{ urlencode($blog->title . ' — ' . url()->current()) }}" target="_blank" rel="noreferrer"
                   class="w-9 h-9 rounded-full bg-[#25D366]/10 hover:bg-[#25D366] hover:text-white text-[#25D366] flex items-center justify-center transition" title="Share on WhatsApp">
                    <i class="fa-brands fa-whatsapp"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($blog->title) }}&url={{ urlencode(url()->current()) }}" target="_blank" rel="noreferrer"
                   class="w-9 h-9 rounded-full bg-ink-900/5 hover:bg-ink-950 hover:text-white text-ink-900 flex items-center justify-center transition" title="Share on Twitter/X">
                    <i class="fa-brands fa-x-twitter"></i>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noreferrer"
                   class="w-9 h-9 rounded-full bg-[#1877F2]/10 hover:bg-[#1877F2] hover:text-white text-[#1877F2] flex items-center justify-center transition" title="Share on Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
            </div>

            @if($related->count())
                <hr class="my-16 border-ink-900/10">
                <h2 class="font-display font-bold text-2xl mb-6">Read next</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($related as $r)
                        <a href="{{ route('blog.show', $r->slug) }}" class="group block pz-tilt-card">
                            <div class="aspect-[4/3] bg-ink-900/5 rounded-xl overflow-hidden mb-3">
                                <img src="{{ $r->cover_image_url ?: 'https://images.unsplash.com/photo-1554995207-c18c203602cb?w=600&q=80' }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition" alt="{{ $r->title }}" loading="lazy">
                            </div>
                            <h3 class="font-display font-bold text-base leading-snug group-hover:text-coral-600 transition">{{ $r->title }}</h3>
                            <p class="text-xs text-ink-900/40 mt-2">{{ $r->reading_time }} min read</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </article>

        {{-- Sidebar --}}
        <aside class="space-y-8">
            @if(($popular ?? collect())->count())
            <div class="bg-cream rounded-2xl border border-ink-900/8 p-6">
                <h3 class="font-display font-bold text-lg mb-4"><i class="fa-solid fa-fire fa-fw"></i> Popular reads</h3>
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
                <h3 class="font-display font-bold text-lg mb-2"><i class="fa-solid fa-envelope-open fa-fw"></i> Get PG tips in your inbox</h3>
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

            <div class="bg-white rounded-2xl border border-ink-900/8 p-6 text-center sticky top-24">
                <div class="text-3xl mb-2"><i class="fa-solid fa-house fa-fw"></i></div>
                <h3 class="font-display font-bold text-lg">Looking for a PG?</h3>
                <p class="text-sm text-ink-900/60 mt-1 mb-4">Browse verified listings across Delhi NCR.</p>
                <a href="{{ route('search') }}" class="inline-block w-full py-2.5 bg-coral-500 hover:bg-coral-600 text-white rounded-xl font-bold text-sm transition">Browse PGs →</a>
            </div>
        </aside>
    </div>
</div>
@endsection
