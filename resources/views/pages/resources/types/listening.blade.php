<x-layout :title="$resource->seo_title ?: $resource->title.' – engleza.wiki'" :description="$resource->seo_description ?: $resource->excerpt">
<article class="mx-auto max-w-3xl px-6 py-12">
  <nav class="text-sm text-navy-500 flex items-center gap-2" data-testid="breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a><span>/</span>
    <a href="{{ route('resources.index') }}" class="hover:text-brand-600">Resources</a><span>/</span>
    <span class="text-navy-900">{{ $resource->title }}</span>
  </nav>
  <div class="mt-6 flex flex-wrap gap-2">
    <span class="chip bg-purple-50 text-purple-700 font-semibold">LISTENING</span>
    @if($resource->level)<span class="chip">{{ $resource->level->name }}</span>@endif
  </div>
  <h1 class="mt-4 text-4xl md:text-5xl font-extrabold text-navy-900" data-testid="resource-title">{{ $resource->title }}</h1>
  <p class="mt-3 text-lg text-navy-600">{{ $resource->excerpt }}</p>

  @php $lr = $resource->listeningResource; @endphp
  @if($lr?->instructions)<div class="mt-6 card p-5 bg-purple-50/60 border-purple-100"><p class="text-navy-800">{{ $lr->instructions }}</p></div>@endif

  <div class="mt-8 card p-6" data-testid="listening-player">
    @if($lr?->media_path)
      @if(str_starts_with((string)$lr->mime_type,'video/') || $lr->media_type==='video')
        <video controls preload="metadata" class="w-full rounded-xl" data-testid="listening-video"><source src="{{ $lr->mediaUrl() }}" type="{{ $lr->mime_type ?? 'video/mp4' }}"></video>
      @else
        <audio controls preload="metadata" class="w-full" data-testid="listening-audio"><source src="{{ $lr->mediaUrl() }}" type="{{ $lr->mime_type ?? 'audio/mpeg' }}"></audio>
      @endif
      <div class="mt-3 text-xs text-navy-500">{{ $lr->original_filename }} · {{ $lr->file_size ? number_format($lr->file_size/1024,0).' KB' : '' }}</div>
    @else
      <div class="text-navy-600 text-sm" data-testid="listening-no-media">Media file will be added by the administrator. Practice the questions below.</div>
    @endif
  </div>

  <livewire:resources.exercise-runner :resource="$resource" />

  @auth
    <form method="POST" action="{{ route('resources.toggle-save',$resource->slug) }}" class="mt-10">@csrf<button class="btn-outline" data-testid="save-resource-btn">☆ Save for later</button></form>
  @endauth

  @include('pages.resources.partials.related', ['related'=>$related])
</article>
</x-layout>
