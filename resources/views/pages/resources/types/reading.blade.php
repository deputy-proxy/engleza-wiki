<x-layout :title="$resource->seo_title ?: $resource->title.' – engleza.wiki'" :description="$resource->seo_description ?: $resource->excerpt">
<article class="mx-auto max-w-6xl px-6 py-12">
  <nav class="text-sm text-navy-500 flex items-center gap-2" data-testid="breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a><span>/</span>
    <a href="{{ route('resources.index') }}" class="hover:text-brand-600">Resources</a><span>/</span>
    <span class="text-navy-900">{{ $resource->title }}</span>
  </nav>
  <div class="mt-6 flex flex-wrap gap-2">
    <span class="chip bg-rose-50 text-rose-700 font-semibold">READING</span>
    @if($resource->level)<span class="chip">{{ $resource->level->name }}</span>@endif
  </div>
  <h1 class="mt-4 text-4xl md:text-5xl font-extrabold text-navy-900" data-testid="resource-title">{{ $resource->title }}</h1>
  <p class="mt-3 text-lg text-navy-600">{{ $resource->excerpt }}</p>

  @php $rr = $resource->readingResource; @endphp
  <div class="mt-8 grid lg:grid-cols-2 gap-8">
    <div class="card p-8" data-testid="reading-passage">
      @if($rr?->title)<h2 class="text-2xl font-bold text-navy-900">{{ $rr->title }}</h2>@endif
      @if($rr?->instructions)<p class="mt-2 text-sm text-navy-500">{{ $rr->instructions }}</p>@endif
      <div class="prose-content mt-4 leading-relaxed text-navy-800">{!! nl2br(e($rr?->content ?? '')) !!}</div>
    </div>
    <div>
      <livewire:resources.exercise-runner :resource="$resource" />
    </div>
  </div>

  @auth
    <form method="POST" action="{{ route('resources.toggle-save',$resource->slug) }}" class="mt-10">@csrf<button class="btn-outline" data-testid="save-resource-btn">☆ Save for later</button></form>
  @endauth

  @include('pages.resources.partials.related', ['related'=>$related])
</article>
</x-layout>
