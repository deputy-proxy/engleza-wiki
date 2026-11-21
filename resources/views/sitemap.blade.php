<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($staticUrls as $path)
    <url><loc>{{ $canonicalBase.($path === '/' ? '' : $path) }}</loc></url>
@endforeach
@foreach($resources as $resource)
    <url>
        <loc>{{ $resource->sitemap_url }}</loc>
        @if($resource->updated_at)<lastmod>{{ $resource->updated_at->toAtomString() }}</lastmod>@endif
    </url>
@endforeach
@foreach($tests as $test)
    <url>
        <loc>{{ $canonicalBase.route('tests.show', $test->slug, false) }}</loc>
        @if($test->updated_at)<lastmod>{{ $test->updated_at->toAtomString() }}</lastmod>@endif
    </url>
@endforeach
@foreach($categories as $category)
    <url><loc>{{ $canonicalBase.route('categories.show', $category->slug, false) }}</loc></url>
@endforeach
@foreach($learningPaths as $path)
    <url>
        <loc>{{ $canonicalBase.route('learning.paths.show', $path->slug, false) }}</loc>
        @if($path->updated_at)<lastmod>{{ $path->updated_at->toAtomString() }}</lastmod>@endif
    </url>
@endforeach
</urlset>
