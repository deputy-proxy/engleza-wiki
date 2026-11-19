<x-layout :resource="$resource" :title="$resource->translated('seo_title') ?: $resource->translated('title').' – engleza.wiki'" :description="$resource->translated('seo_description') ?: $resource->translated('excerpt')">
<article class="mx-auto max-w-4xl px-6 py-12">
  <nav class="text-sm text-navy-500 flex items-center gap-2" data-testid="breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-brand-600">{{ __('Home') }}</a><span>/</span>
    <a href="{{ route('resources.index') }}" class="hover:text-brand-600">{{ __('Resources') }}</a><span>/</span>
    <span class="text-navy-900">{{ $resource->translated('title') }}</span>
  </nav>
  <div class="mt-6 flex flex-wrap gap-2">
    <span class="chip bg-emerald-50 text-emerald-700 font-semibold">{{ __('SPEAKING') }}</span>
    @if($resource->level)<span class="chip">{{ $resource->level->translated('name') }}</span>@endif
    @foreach($resource->skills as $skill)<span class="chip">{{ $skill->translated('name') }}</span>@endforeach
  </div>
  <h1 class="mt-4 text-4xl md:text-5xl font-extrabold text-navy-900" data-testid="resource-title">{{ $resource->translated('title') }}</h1>
  <p class="mt-3 text-lg text-navy-600">{{ $resource->translated('excerpt') }}</p>
  @include('pages.learning._resource-context')

  @php($practice = $resource->speakingResource)
  <section class="mt-8 card p-6 md:p-8" data-testid="speaking-practice" x-data="speakingPractice()">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-xs uppercase tracking-widest font-semibold text-emerald-700">{{ __('Target skill') }}</p>
        <p class="mt-1 text-lg font-bold text-navy-900">{{ $practice->translated('target_skill') }}</p>
      </div>
      <p class="text-sm text-navy-600">{{ __('Preparation') }}: {{ $practice->preparation_time_seconds }}s · {{ __('Speaking') }}: {{ $practice->response_time_seconds }}s</p>
    </div>

    <div class="mt-6 rounded-2xl bg-emerald-50 p-5 md:p-6">
      <p class="text-xs uppercase tracking-widest font-semibold text-emerald-800">{{ __('Your speaking prompt') }}</p>
      <h2 class="mt-2 text-xl md:text-2xl font-bold leading-relaxed text-navy-900">{{ $practice->translated('prompt') }}</h2>
    </div>

    <div class="mt-6 grid gap-5 md:grid-cols-2">
      <div>
        <h3 class="font-bold text-navy-900">{{ __('How to practise') }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-navy-700">{{ $practice->translated('instructions') }}</p>
        <h3 class="mt-5 font-bold text-navy-900">{{ __('What your response should include') }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-navy-700">{{ $practice->translated('response_expectation') }}</p>
      </div>
      <div class="rounded-xl border border-navy-100 p-5">
        <h3 class="font-bold text-navy-900">{{ __('Practice timer') }}</h3>
        <p class="mt-1 text-sm text-navy-600">{{ __('Use the timer as a guide. You can practise without it.') }}</p>
        <div class="mt-4 text-center">
          <p class="text-4xl font-bold tabular-nums text-navy-900" aria-live="off" data-testid="speaking-timer" x-text="phase === 'ready' ? '—:—' : clock">—:—</p>
          <p class="mt-1 text-sm text-navy-600" role="status" aria-live="polite" aria-atomic="true" data-testid="speaking-timer-status">
            <span x-show="phase === 'ready'">{{ __('Ready when you are') }}</span>
            <span x-show="phase === 'preparation'">{{ __('Prepare your ideas') }}</span>
            <span x-show="phase === 'speaking'">{{ __('Speak aloud now') }}</span>
            <span x-show="phase === 'done'">{{ __('Time is up') }}</span>
          </p>
        </div>
        <noscript><p class="mt-3 text-xs text-navy-500">{{ __('The timer needs JavaScript. You can still use the task and keep time yourself.') }}</p></noscript>
        <div class="mt-4 flex flex-wrap justify-center gap-2">
          <button type="button" class="btn-outline" x-on:click="start('preparation', {{ $practice->preparation_time_seconds }})" x-bind:disabled="phase === 'preparation' || phase === 'speaking'">{{ __('Start preparation') }}</button>
          <button type="button" class="btn-primary" x-on:click="start('speaking', {{ $practice->response_time_seconds }})" x-bind:disabled="phase === 'speaking'">{{ __('Start speaking') }}</button>
          <button type="button" class="btn-ghost" x-on:click="reset()">{{ __('Reset') }}</button>
        </div>
      </div>
    </div>

    <div class="mt-6">
      <h3 class="font-bold text-navy-900">{{ __('Self-check') }}</h3>
      <p class="mt-1 text-sm text-navy-600">{{ __('After speaking, review these criteria. They are reflection prompts, not an automated score.') }}</p>
      <ul class="mt-3 space-y-2 text-sm text-navy-700">
        @foreach(preg_split('/\r\n|\r|\n/', $practice->translated('success_criteria')) as $criterion)
          @if(trim($criterion))<li class="flex items-start gap-2"><span aria-hidden="true" class="mt-0.5 text-emerald-700">✓</span><span>{{ trim($criterion) }}</span></li>@endif
        @endforeach
      </ul>
    </div>
    <div class="mt-6 rounded-xl bg-navy-50 p-5">
      <h3 class="font-bold text-navy-900">{{ __('Reflect on your practice') }}</h3>
      <p class="mt-2 text-sm text-navy-700">{{ $practice->translated('reflection_prompt') }}</p>
      <label for="speaking-reflection" class="mt-4 block text-sm font-semibold text-navy-800">{{ __('Your reflection (optional)') }}</label>
      <textarea id="speaking-reflection" rows="3" class="mt-2 w-full rounded-lg border border-navy-200 bg-white p-3 text-sm text-navy-900" placeholder="{{ __('Write a note for yourself') }}"></textarea>
      <p class="mt-2 text-xs text-navy-500">{{ __('This note stays in this page and is not sent or saved.') }}</p>
    </div>
    <p class="mt-6 text-xs leading-relaxed text-navy-500">{{ __('This activity does not record or upload your voice, access your microphone, transcribe speech or calculate a pronunciation score. Your speaking practice stays with you.') }}</p>
  </section>

  @auth
    <form method="POST" action="{{ route('resources.toggle-save',$resource->slug) }}" class="mt-8">@csrf<button class="btn-outline" data-testid="save-resource-btn">☆ {{ __('Save for later') }}</button></form>
  @endauth
  @include('pages.resources.partials.related', ['related'=>$related])
</article>
</x-layout>
