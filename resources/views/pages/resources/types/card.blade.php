<x-layout :title="$resource->seo_title ?: $resource->title.' – engleza.wiki'" :description="$resource->seo_description ?: $resource->excerpt">
<article class="mx-auto max-w-3xl px-6 py-12">
  <nav class="text-sm text-navy-500 flex items-center gap-2" data-testid="breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a><span>/</span>
    <a href="{{ route('resources.index') }}" class="hover:text-brand-600">Resources</a><span>/</span>
    <span class="text-navy-900">{{ $resource->title }}</span>
  </nav>
  <div class="mt-6 flex flex-wrap gap-2">
    <span class="chip bg-yellow-50 text-yellow-700 font-semibold">CARDS</span>
    @if($resource->level)<span class="chip">{{ $resource->level->name }}</span>@endif
    @foreach($resource->skills as $s)<span class="chip bg-emerald-50 text-emerald-700">{{ $s->name }}</span>@endforeach
  </div>
  <h1 class="mt-4 text-4xl md:text-5xl font-extrabold text-navy-900" data-testid="resource-title">{{ $resource->title }}</h1>
  <p class="mt-3 text-lg text-navy-600">{{ $resource->excerpt }}</p>
  @include('pages.learning._resource-context')

  @if($resource->cardResource?->instructions)
    <div class="mt-6 card p-5 bg-brand-50/60 border-brand-100"><p class="text-navy-800">{{ $resource->cardResource->instructions }}</p></div>
  @endif

  <livewire:resources.interactive-cards :resource="$resource" />

  @auth
    <form method="POST" action="{{ route('resources.toggle-save',$resource->slug) }}" class="mt-10">@csrf<button class="btn-outline" data-testid="save-resource-btn">☆ Save for later</button></form>
  @endauth

  @include('pages.resources.partials.related', ['related'=>$related])
</article>
</x-layout>
