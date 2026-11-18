<x-layout :title="$lesson->translated('title').' · '.$lesson->unit->path->translated('title')" :description="$lesson->translated('description')">
<section class="mx-auto max-w-4xl px-6 py-12">
  <a href="{{ route('learning.paths.show', $lesson->unit->path) }}" class="text-sm font-semibold text-brand-600 hover:underline">← {{ $lesson->unit->path->translated('title') }}</a>
  <div class="mt-6 flex flex-wrap gap-2">
    @if($lesson->level)<span class="chip bg-brand-50 text-brand-700">{{ $lesson->level->translated('name') }}</span>@endif
    @foreach($lesson->skills as $skill)<span class="chip">{{ $skill->translated('name') }}</span>@endforeach
  </div>
  <h1 class="mt-4 text-4xl font-extrabold text-navy-900">{{ $lesson->translated('title') }}</h1>
  @if($lesson->translated('description'))<p class="mt-3 text-lg text-navy-600">{{ $lesson->translated('description') }}</p>@endif
  @if(is_array($lesson->objectives) && count($lesson->objectives))
    <div class="mt-6 rounded-2xl bg-brand-50 p-5">
      <h2 class="font-bold text-navy-900">{{ __('In this lesson') }}</h2>
      <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-navy-700">
        @foreach($lesson->objectives as $objective)<li>{{ $objective }}</li>@endforeach
      </ul>
    </div>
  @endif
  @if(session('status'))<p class="mt-5 text-sm font-semibold text-emerald-700">{{ session('status') }}</p>@endif

  @php $nextActivityId = $lesson->activities->first(fn ($activity) => ! $activityProgress->has($activity->id))?->id; @endphp
  <div class="mt-8 space-y-4">
    @foreach($lesson->activities as $activity)
      @php
        $completed = $activityProgress->has($activity->id);
        $target = $activity->type === 'test' ? $activity->test : $activity->resource;
        $targetUrl = $activity->type === 'test' && $target ? route('tests.show', $target->slug) : ($target ? route('resources.show', $target->slug) : '#');
        $attemptId = $attemptIds[$activity->id] ?? null;
      @endphp
      <article class="card p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex flex-wrap items-center gap-2">
            <p class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ __('Activity') }} {{ $loop->iteration }} · {{ __($activity->type) }}</p>
            @if($activity->id === $nextActivityId)<span class="chip bg-brand-50 text-brand-700">{{ __('Next activity') }}</span>@endif
            @if(!$activity->is_required)<span class="chip">{{ __('Optional') }}</span>@endif
          </div>
          @if($completed)<span class="chip bg-emerald-50 text-emerald-700">{{ __('Completed') }}</span>@endif
        </div>
        <h2 class="mt-2 text-xl font-bold text-navy-900">{{ $activity->translated('title') ?: ($target->translated('title') ?? __('Activity')) }}</h2>
        @if($activity->translated('instructions'))<p class="mt-2 text-sm text-navy-600">{{ $activity->translated('instructions') }}</p>@endif
        <div class="mt-4 flex flex-wrap items-center gap-3">
          <a href="{{ $targetUrl }}" class="btn-outline">{{ __('Open activity') }}</a>
          @if(!$completed)
            <form method="POST" action="{{ route('learning.activities.complete', $activity) }}" class="flex flex-wrap items-center gap-2">
              @csrf
              @if($attemptId)<input type="hidden" name="attempt_id" value="{{ $attemptId }}">@endif
              <button class="btn-primary" @disabled(!($activityCompletionReady[$activity->id] ?? false))>{{ __('Mark complete') }}</button>
              @unless($activityCompletionReady[$activity->id] ?? false)<span class="text-xs text-navy-500">{{ __('Complete and submit this activity first.') }}</span>@endunless
            </form>
          @endif
        </div>
      </article>
    @endforeach
  </div>

  <form method="POST" action="{{ route('learning.lessons.complete', $lesson) }}" class="mt-8 flex justify-end">
    @csrf
    <button class="btn-primary bg-emerald-600 hover:bg-emerald-700">{{ __('Complete lesson') }}</button>
  </form>
</section>
</x-layout>
