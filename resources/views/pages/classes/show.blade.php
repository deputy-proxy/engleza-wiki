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

  @if($group->writingResources->isNotEmpty())
    <h2 class="mt-10 text-2xl font-bold text-navy-900">{{ __('Writing assignments') }}</h2>
    <div class="mt-4 space-y-4">
      @foreach($group->writingResources as $writing)
        <article class="card p-6" data-testid="class-writing-{{ $writing->id }}">
          <div class="text-xs uppercase tracking-wide text-sky-700 font-semibold">{{ $writing->resource->level?->translated('name') ?? __('Writing') }}</div>
          <h3 class="mt-2 text-xl font-bold text-navy-900">{{ $writing->resource->translated('title') }}</h3>
          <p class="mt-2 text-navy-600">{{ $writing->resource->translated('excerpt') }}</p>
          <p class="mt-3 text-navy-700">{{ $writing->translated('prompt') }}</p>
          @auth
            <form method="POST" action="{{ route('classes.writing.submit', [$group, $writing->resource]) }}" class="mt-5" data-testid="class-writing-form-{{ $writing->id }}">
              @csrf
              <label class="block text-sm font-semibold text-navy-700" for="class-writing-content-{{ $writing->id }}">{{ __('Your response') }}</label>
              <textarea id="class-writing-content-{{ $writing->id }}" name="content" rows="8" required maxlength="20000" class="mt-2 w-full rounded-xl border border-navy-200 p-4 text-navy-900">{{ old('content') }}</textarea>
              <div class="mt-3 flex justify-end gap-3">
                <button name="status" value="draft" class="btn-outline">{{ __('Save draft') }}</button>
                <button name="status" value="submitted" class="btn-primary">{{ __('Submit to teacher') }}</button>
              </div>
            </form>
          @else
            <p class="mt-4 text-sm text-navy-600"><a class="font-semibold text-brand-700" href="{{ route('login') }}">{{ __('Log in') }}</a> {{ __('to submit this assignment.') }}</p>
          @endauth
        </article>
      @endforeach
    </div>
  @endif
</section>
</x-layout>
