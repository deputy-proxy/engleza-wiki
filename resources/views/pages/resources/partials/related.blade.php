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
