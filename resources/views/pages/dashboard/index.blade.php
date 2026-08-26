<x-layout :title="'Dashboard – engleza.wiki'">
<section class="mx-auto max-w-6xl px-6 py-12">
  <h1 class="text-3xl font-extrabold text-navy-900">Welcome, {{ auth()->user()->name }}</h1>
  <p class="text-navy-600 mt-1">Your English progress at a glance.</p>
  <div class="mt-8 grid md:grid-cols-3 gap-6">
    <div class="card p-6"><div class="text-sm text-navy-500">Tests completed</div><div class="mt-2 text-4xl font-extrabold text-navy-900" data-testid="stat-tests">{{ $completed->count() }}</div></div>
    <div class="card p-6"><div class="text-sm text-navy-500">Average score</div><div class="mt-2 text-4xl font-extrabold text-navy-900" data-testid="stat-avg">{{ $avg }}%</div></div>
    <div class="card p-6"><div class="text-sm text-navy-500">Latest level</div><div class="mt-2 text-4xl font-extrabold text-brand-600" data-testid="stat-level">{{ $latestLevel ?? '—' }}</div></div>
  </div>
  <h2 class="mt-12 text-2xl font-bold text-navy-900">Recent tests</h2>
  <div class="mt-4 space-y-3">
    @forelse($attempts as $a)
      <a href="{{ route('tests.result',['test'=>$a->test->slug,'attempt'=>$a->id]) }}" class="card p-5 flex items-center justify-between hover:shadow-lg">
        <div><div class="font-bold text-navy-900">{{ $a->test->title }}</div><div class="text-sm text-navy-500">{{ $a->created_at->diffForHumans() }}</div></div>
        <div class="text-right"><div class="font-bold text-navy-900">{{ $a->percentage ?? 0 }}%</div><div class="text-sm text-brand-600">{{ $a->level_result ?? $a->status }}</div></div>
      </a>
    @empty <p class="text-navy-600">No tests yet. <a class="text-brand-600 font-semibold" href="{{ route('tests.index') }}">Take one →</a></p>
    @endforelse
  </div>
  <h2 class="mt-12 text-2xl font-bold text-navy-900">Saved resources</h2>
  <div class="mt-4 grid md:grid-cols-2 gap-4">
    @forelse($saved as $s)
      <a href="{{ route('resources.show',$s->resource->slug) }}" class="card p-5 hover:shadow-lg"><div class="font-bold text-navy-900">{{ $s->resource->title }}</div></a>
    @empty <p class="text-navy-600 col-span-full">Nothing saved yet.</p>
    @endforelse
  </div>
</section>
</x-layout>
