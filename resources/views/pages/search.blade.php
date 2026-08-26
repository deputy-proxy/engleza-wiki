<x-layout :title="'Search – engleza.wiki'">
<section class="mx-auto max-w-7xl px-6 py-12">
  <h1 class="text-4xl font-extrabold text-navy-900">Search</h1>
  <form method="GET" class="mt-6 flex gap-2 max-w-2xl">
    <input name="q" value="{{ $term }}" placeholder="Search..." class="flex-1 rounded-full border border-navy-200 px-5 py-3 text-sm" data-testid="search-input">
    <button class="btn-primary" data-testid="search-submit">Search</button>
  </form>
  @if($term)
    <p class="mt-6 text-navy-600" data-testid="search-summary">Showing results for <strong>"{{ $term }}"</strong>.</p>
    @if($tests->isNotEmpty())
      <h2 class="mt-10 text-xl font-bold text-navy-900">Tests</h2>
      <div class="mt-4 grid md:grid-cols-2 gap-4">
        @foreach($tests as $t)
          <a href="{{ route('tests.show',$t->slug) }}" class="card p-5 hover:shadow-lg"><div class="font-bold text-navy-900">{{ $t->title }}</div><div class="text-sm text-navy-600 mt-1">{{ $t->description }}</div></a>
        @endforeach
      </div>
    @endif
    <h2 class="mt-10 text-xl font-bold text-navy-900">Resources</h2>
    <div class="mt-4 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      @forelse($resources as $r)
        <a href="{{ route('resources.show',$r->slug) }}" class="card p-5 hover:shadow-lg">
          <div class="text-xs text-brand-600 font-semibold">{{ ucfirst(str_replace('_',' ',$r->type)) }}</div>
          <div class="mt-2 font-bold text-navy-900">{{ $r->title }}</div>
          <div class="mt-1 text-sm text-navy-600 line-clamp-2">{{ $r->excerpt }}</div>
        </a>
      @empty<div class="col-span-full text-navy-600">No matches.</div>@endforelse
    </div>
  @endif
</section>
</x-layout>
