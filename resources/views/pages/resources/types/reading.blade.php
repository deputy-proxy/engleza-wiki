<x-layout :resource="$resource" :title="$resource->translated('seo_title') ?: $resource->translated('title').' – engleza.wiki'" :description="$resource->translated('seo_description') ?: $resource->translated('excerpt')">
<article class="mx-auto max-w-6xl px-6 py-12">
  <nav aria-label="{{ __('Breadcrumb') }}" class="text-sm text-navy-500 flex items-center gap-2" data-testid="breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('Home') }}</a><span>/</span>
    <a href="{{ route('resources.index') }}" class="hover:text-brand-600">{{ __('Resources') }}</a><span>/</span>
    <span class="text-navy-900">{{ $resource->translated('title') }}</span>
  </nav>
  <div class="mt-6 flex flex-wrap gap-2">
    <span class="chip bg-rose-50 text-rose-700 font-semibold">{{ __('READING') }}</span>
    @if($resource->level)<span class="chip">{{ $resource->level->translated('name') }}</span>@endif
  </div>
  <h1 class="mt-4 text-4xl md:text-5xl font-extrabold text-navy-900" data-testid="resource-title">{{ $resource->translated('title') }}</h1>
  <p class="mt-3 text-lg text-navy-600">{{ $resource->translated('excerpt') }}</p>
  @include('pages.learning._resource-context')

  @php $rr = $resource->readingResource; @endphp
  <div class="mt-8 grid lg:grid-cols-2 gap-8">
    <div class="card p-8" data-testid="reading-passage">
      @if($rr?->title)<h2 class="text-2xl font-bold text-navy-900">{{ $rr->translated('title') }}</h2>@endif
      @if($rr?->instructions)<p class="mt-2 text-sm text-navy-500">{{ $rr->translated('instructions') }}</p>@endif
      <div class="prose-content mt-4 leading-relaxed text-navy-800">{!! nl2br(e($rr?->content ?? '')) !!}</div>
    </div>
    <div>
      <livewire:resources.exercise-runner :resource="$resource" />
    </div>
  </div>

  @auth
    <form method="POST" action="{{ route('resources.toggle-save',$resource->slug) }}" class="mt-10">@csrf<button class="btn-outline" data-testid="save-resource-btn">☆ {{ __('Save for later') }}</button></form>
  @endauth

  @include('pages.resources.partials.related', ['related'=>$related])
</article>
</x-layout>
