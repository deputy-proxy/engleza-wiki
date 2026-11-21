<x-layout :robots-index="false" :title="__('Search – engleza.wiki')">
<section class="mx-auto max-w-7xl px-6 py-12">
  <h1 class="text-4xl font-extrabold text-navy-900">{{ __('Search') }}</h1>
  <form role="search" aria-label="{{ __('Search resources and tests') }}" method="GET" class="mt-6 flex gap-2 max-w-2xl">
    <label for="search-query" class="sr-only">{{ __('Search resources and tests') }}</label><input id="search-query" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Search...') }}" class="flex-1 rounded-full border border-navy-200 px-5 py-3 text-sm" data-testid="search-input">
    <button type="submit" class="btn-primary" data-testid="search-submit">{{ __('Search') }}</button>
  </form>
  @if($term)
    <p class="mt-6 text-navy-600" data-testid="search-summary">{{ __('Showing results for ') }}<strong>"{{ $term }}"</strong>.</p>
    @if($tests->isNotEmpty())
      <h2 class="mt-10 text-xl font-bold text-navy-900">{{ __('Tests') }}</h2>
      <div class="mt-4 grid md:grid-cols-2 gap-4">
        @foreach($tests as $t)
          <a href="{{ route('tests.show',$t->slug) }}" class="card p-5 hover:shadow-lg"><div class="font-bold text-navy-900">{{ $t->translated('title') }}</div><div class="text-sm text-navy-600 mt-1">{{ $t->translated('description') }}</div></a>
        @endforeach
      </div>
    @endif
    <h2 class="mt-10 text-xl font-bold text-navy-900">{{ __('Resources') }}</h2>
    <div class="mt-4 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      @forelse($resources as $r)
        <a href="{{ route('resources.show',$r->slug) }}" class="card p-5 hover:shadow-lg">
          <div class="text-xs text-brand-600 font-semibold">{{ __($r->type) }}</div>
          <div class="mt-2 font-bold text-navy-900">{{ $r->translated('title') }}</div>
          <div class="mt-1 text-sm text-navy-600 line-clamp-2">{{ $r->translated('excerpt') }}</div>
        </a>
      @empty<div class="col-span-full text-navy-600">{{ __('No matches.') }}</div>@endforelse
    </div>
  @endif
</section>
</x-layout>
