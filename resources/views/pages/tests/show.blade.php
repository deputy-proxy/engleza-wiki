<x-layout :title="$test->translated('title').' – engleza.wiki'">
<section class="mx-auto max-w-3xl px-6 py-12">
  <div class="flex gap-2">
    @if($test->level)<span class="chip">{{ $test->level->translated('name') }}</span>@endif
    <span class="chip bg-yellow-50 text-yellow-700">{{ __(ucfirst($test->access_type)) }}</span>
  </div>
  <h1 class="mt-4 text-4xl font-extrabold text-navy-900" data-testid="test-title">{{ $test->translated('title') }}</h1>
  <p class="mt-3 text-navy-600">{{ $test->translated('description') }}</p>
  @if(str_starts_with($test->slug, 'english-level-test-'))
    <p class="mt-3 rounded-lg bg-amber-50 p-4 text-sm text-amber-900">{{ __('This is an indicative practice estimate, not an official CEFR examination or certificate. It samples grammar, vocabulary and reading only; it does not assess listening, speaking or writing.') }}</p>
  @endif
  <div class="mt-8 card p-6">
    <h2 class="text-lg font-bold text-navy-900">{{ __('Instructions') }}</h2>
    <p class="mt-2 text-navy-600 whitespace-pre-line">{{ $test->translated('instructions') }}</p>
    <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
      <div><div class="text-navy-500">{{ __('Questions') }}</div><div class="font-bold text-navy-900">{{ $questionCount }}</div></div>
      <div><div class="text-navy-500">{{ __('Time') }}</div><div class="font-bold text-navy-900">{{ $test->duration_minutes ?? '—' }} {{ __('min') }}</div></div>
      <div><div class="text-navy-500">{{ __('Passing') }}</div><div class="font-bold text-navy-900">{{ $test->passing_score ?? '—' }}%</div></div>
    </div>
  </div>
  <form method="POST" action="{{ route('tests.start',$test->slug) }}" class="mt-6">
    @csrf
    <button class="btn-primary" data-testid="start-test-btn">{{ __('Start test') }}</button>
  </form>
</section>
</x-layout>
