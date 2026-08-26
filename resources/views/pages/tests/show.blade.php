<x-layout :title="$test->title.' – engleza.wiki'">
<section class="mx-auto max-w-3xl px-6 py-12">
  <div class="flex gap-2">
    @if($test->level)<span class="chip">{{ $test->level->name }}</span>@endif
    <span class="chip bg-yellow-50 text-yellow-700">{{ ucfirst($test->access_type) }}</span>
  </div>
  <h1 class="mt-4 text-4xl font-extrabold text-navy-900" data-testid="test-title">{{ $test->title }}</h1>
  <p class="mt-3 text-navy-600">{{ $test->description }}</p>
  <div class="mt-8 card p-6">
    <h2 class="text-lg font-bold text-navy-900">Instructions</h2>
    <p class="mt-2 text-navy-600 whitespace-pre-line">{{ $test->instructions }}</p>
    <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
      <div><div class="text-navy-500">Questions</div><div class="font-bold text-navy-900">{{ $questionCount }}</div></div>
      <div><div class="text-navy-500">Time</div><div class="font-bold text-navy-900">{{ $test->duration_minutes ?? '—' }} min</div></div>
      <div><div class="text-navy-500">Passing</div><div class="font-bold text-navy-900">{{ $test->passing_score ?? '—' }}%</div></div>
    </div>
  </div>
  <form method="POST" action="{{ route('tests.start',$test->slug) }}" class="mt-6">
    @csrf
    <button class="btn-primary" data-testid="start-test-btn">Start test</button>
  </form>
</section>
</x-layout>
