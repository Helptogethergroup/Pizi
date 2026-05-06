@extends('layouts.app')
@section('title', ($blog->meta_title ?: $blog->title) . ' | PGFind')
@section('meta_description', $blog->meta_description ?: Str::limit(strip_tags($blog->excerpt ?? ''), 160))
@section('og_image', $blog->cover_image ?: '')

@section('schema')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": @json($blog->title),
  "image": @json($blog->cover_image),
  "datePublished": @json($blog->published_at?->toIso8601String()),
  "author": { "@type": "Organization", "name": "PGFind" }
}
</script>
@endsection

@section('content')
<article class="max-w-3xl mx-auto px-4 lg:px-8 py-16">
    <span class="text-xs font-semibold text-coral-600 uppercase tracking-wider">{{ $blog->published_at?->format('M d, Y') }}</span>
    <h1 class="font-display font-black text-4xl md:text-6xl leading-[1.05] mt-3">{{ $blog->title }}</h1>

    @if($blog->cover_image)
        <img src="{{ $blog->cover_image }}" alt="{{ $blog->title }}" class="w-full aspect-[16/9] object-cover rounded-3xl mt-10">
    @endif

    <div class="prose prose-lg mt-10 text-ink-900/85 leading-relaxed">
        {!! $blog->content !!}
    </div>

    @if($related->count())
        <hr class="my-16 border-ink-900/10">
        <h2 class="font-display font-bold text-2xl mb-6">Read next</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($related as $r)
                <a href="{{ route('blog.show', $r->slug) }}" class="group block">
                    <h3 class="font-display font-bold text-lg group-hover:text-coral-600">{{ $r->title }}</h3>
                </a>
            @endforeach
        </div>
    @endif
</article>
@endsection
