<x-layout :robots-index="false" :title="$group->name.' — engleza.wiki'">
<section class="mx-auto max-w-3xl px-6 py-12">
  <div class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ __('Class join code') }}</div>
  <div class="mt-2 text-4xl font-mono font-extrabold tracking-widest text-navy-900" data-testid="class-code">{{ $group->join_code }}</div>
  <h1 class="mt-4 text-4xl font-extrabold text-navy-900" data-testid="class-name">{{ $group->name }}</h1>
  <p class="mt-2 text-navy-600">{{ __('Invited by ') }}<strong>{{ $group->teacher?->name ?? __('a teacher') }}</strong>. {{ __('When you take the test below, your attempt will appear in your teacher\'s class results.') }}</p>
  @if($group->description)<p class="mt-3 text-navy-700">{{ $group->description }}</p>@endif

  @if($group->test)
    <div class="mt-8 card p-6" data-testid="class-test-card">
      <div class="flex items-center gap-2 mb-2">
        @if($group->test->level)<span class="chip">{{ $group->test->level->translated('name') }}</span>@endif
        <span class="chip bg-yellow-50 text-yellow-700">{{ __('Free') }}</span>
      </div>
      <h2 class="text-xl font-bold text-navy-900">{{ $group->test->translated('title') }}</h2>
      <p class="mt-1 text-navy-600">{{ $group->test->translated('description') }}</p>
      <form method="POST" action="{{ route('tests.start', $group->test->slug) }}" class="mt-4">
        @csrf
        <input type="hidden" name="class_code" value="{{ $group->join_code }}">
        <button class="btn-primary" data-testid="class-start-btn">{{ __('Start the class test') }}</button>
      </form>
    </div>
  @else
    <div class="mt-6 card p-5 bg-navy-50/60 text-navy-700">{{ __('No specific test has been assigned to this class yet. Ask your teacher for details.') }}</div>
  @endif
</section>
</x-layout>
