<x-layout :resource="$resource" :title="$resource->translated('seo_title') ?: $resource->translated('title').' – engleza.wiki'" :description="$resource->translated('seo_description') ?: $resource->translated('excerpt')">
<article class="mx-auto max-w-4xl px-6 py-12">
  <nav class="text-sm text-navy-500 flex items-center gap-2" data-testid="breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('Home') }}</a><span>/</span>
    <a href="{{ route('resources.index') }}" class="hover:text-brand-600">{{ __('Resources') }}</a><span>/</span>
    <span class="text-navy-900">{{ $resource->translated('title') }}</span>
  </nav>
  <div class="mt-6 flex flex-wrap gap-2">
    @if($resource->level)<span class="chip">{{ $resource->level->translated('name') }}</span>@endif
    @foreach($resource->skills as $s)<span class="chip bg-emerald-50 text-emerald-700">{{ $s->translated('name') }}</span>@endforeach
    <span class="chip bg-brand-50 text-brand-700">{{ __(ucfirst(str_replace('_',' ',$resource->type))) }}</span>
  </div>
  <h1 class="mt-4 text-4xl md:text-5xl font-extrabold text-navy-900" data-testid="resource-title">{{ $resource->translated('title') }}</h1>
  <p class="mt-3 text-lg text-navy-600">{{ $resource->translated('excerpt') }}</p>
  @include('pages.learning._resource-context')

  @auth
    <form method="POST" action="{{ route('resources.toggle-save',$resource->slug) }}" class="mt-6">
      @csrf
      <button class="btn-outline" data-testid="save-resource-btn">☆ {{ __('Save for later') }}</button>
    </form>
  @endauth

  <div class="prose-content mt-10">
    {!! nl2br(e($resource->translated('content'))) !!}
  </div>

  @if($resource->tags->isNotEmpty())
    <div class="mt-10 flex flex-wrap gap-2">
      @foreach($resource->tags as $tag)<span class="chip">#{{ $tag->translated('name') }}</span>@endforeach
    </div>
  @endif

  @if($related->isNotEmpty())
    <h2 class="mt-14 text-2xl font-bold text-navy-900">{{ __('Recommended for you') }}</h2>
    <div class="mt-6 grid md:grid-cols-2 gap-6">
      @foreach($related as $r)
        <a href="{{ route('resources.show',$r->slug) }}" class="card p-5 hover:shadow-lg transition" data-testid="related-card-{{ $r->slug }}">
          <div class="text-xs text-brand-600 font-semibold">{{ __(ucfirst(str_replace('_',' ',$r->type))) }}</div>
          <div class="mt-2 font-bold text-navy-900">{{ $r->translated('title') }}</div>
          <div class="mt-1 text-sm text-navy-600 line-clamp-2">{{ $r->translated('excerpt') }}</div>
        </a>
      @endforeach
    </div>
  @endif
</article>
</x-layout>
