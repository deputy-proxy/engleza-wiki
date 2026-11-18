<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($staticUrls as $path)
    <url><loc>{{ url($path) }}</loc></url>
@endforeach
@foreach($resources as $resource)
    <url>
        <loc>{{ $resource->canonical_url ?: route('resources.show', $resource->slug) }}</loc>
        @if($resource->updated_at)<lastmod>{{ $resource->updated_at->toAtomString() }}</lastmod>@endif
    </url>
@endforeach
@foreach($tests as $test)
    <url>
        <loc>{{ route('tests.show', $test->slug) }}</loc>
        @if($test->updated_at)<lastmod>{{ $test->updated_at->toAtomString() }}</lastmod>@endif
    </url>
@endforeach
@foreach($categories as $category)
    <url><loc>{{ route('categories.show', $category->slug) }}</loc></url>
@endforeach
@foreach($learningPaths as $path)
    <url>
        <loc>{{ route('learning.paths.show', $path->slug) }}</loc>
        @if($path->updated_at)<lastmod>{{ $path->updated_at->toAtomString() }}</lastmod>@endif
    </url>
@endforeach
</urlset>
