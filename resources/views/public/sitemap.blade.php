<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ url('/') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
    <url><loc>{{ url('/about') }}</loc><priority>0.6</priority></url>
    <url><loc>{{ url('/contact') }}</loc><priority>0.6</priority></url>
    <url><loc>{{ url('/blog') }}</loc><priority>0.7</priority></url>
    @foreach($cities as $c)
        <url><loc>{{ route('city.show', $c->slug) }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
    @endforeach
    @foreach($localities as $l)
        <url><loc>{{ route('locality.show', [$l->city->slug, $l->slug]) }}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
    @endforeach
    @foreach($properties as $p)
        <url><loc>{{ route('property.show', $p->slug) }}</loc><lastmod>{{ $p->updated_at->toAtomString() }}</lastmod><priority>0.7</priority></url>
    @endforeach
    @foreach($blogs as $b)
        <url><loc>{{ route('blog.show', $b->slug) }}</loc><lastmod>{{ $b->updated_at->toAtomString() }}</lastmod><priority>0.6</priority></url>
    @endforeach
    @foreach($landmarks as $lm)
        <url><loc>{{ route('landmark.show', $lm->slug) }}</loc><lastmod>{{ $lm->updated_at->toAtomString() }}</lastmod><priority>0.8</priority></url>
    @endforeach
</urlset>   
