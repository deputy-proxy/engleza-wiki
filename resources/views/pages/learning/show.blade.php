<x-layout :title="$path->translated('title').' · '.__('Learning path')" :description="$path->translated('description')">
<section class="mx-auto max-w-5xl px-6 py-12">
  <a href="{{ route('learning.paths.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">← {{ __('All learning paths') }}</a>
  <div class="mt-6 flex flex-wrap gap-2">
    @if($path->level)<span class="chip bg-brand-50 text-brand-700">{{ $path->level->translated('name') }}</span>@endif
    @foreach($path->skills as $skill)<span class="chip">{{ $skill->translated('name') }}</span>@endforeach
  </div>
  <h1 class="mt-4 text-4xl font-extrabold text-navy-900">{{ $path->translated('title') }}</h1>
  <p class="mt-4 max-w-3xl text-lg text-navy-600">{{ $path->translated('description') }}</p>
  @if($enrollment)
    <div class="mt-6 flex flex-wrap items-center gap-4">
      <span class="chip bg-emerald-50 text-emerald-700">{{ __('Enrolled') }} · {{ __(str_replace('_', ' ', ucfirst($enrollment->status))) }}</span>
      <a href="{{ route('dashboard.learning') }}" class="btn-outline">{{ __('My learning') }}</a>
    </div>
  @elseif(auth()->check())
    <form method="POST" action="{{ route('learning.paths.enroll', $path) }}" class="mt-6">@csrf<button class="btn-primary">{{ __('Start this learning path') }}</button></form>
  @else
    <a href="{{ route('login') }}" class="btn-primary mt-6 inline-flex">{{ __('Sign in to start learning') }}</a>
  @endif

  <div class="mt-12 space-y-8">
    @foreach($path->units->where('is_published', true) as $unit)
      @php $visibleLessons = $availableLessonsByUnit->get($unit->id, collect()); @endphp
      @if($visibleLessons->isNotEmpty())
        <section class="card p-6">
          <div class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ __('Unit') }} {{ $loop->iteration }}</div>
          <h2 class="mt-2 text-2xl font-bold text-navy-900">{{ $unit->translated('title') }}</h2>
          @if($unit->translated('description'))<p class="mt-2 text-navy-600">{{ $unit->translated('description') }}</p>@endif
          <ol class="mt-5 space-y-3">
            @foreach($visibleLessons as $lesson)
              @php $lessonState = $progress->get($lesson->id); @endphp
              <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-navy-100 p-4">
                <div>
                  <div class="font-semibold text-navy-900">{{ $lesson->translated('title') }}</div>
                  @if($lesson->translated('description'))<p class="mt-1 text-sm text-navy-600">{{ $lesson->translated('description') }}</p>@endif
                </div>
                <div class="flex items-center gap-3">
                  @if($lessonState?->status === 'completed')
                    <span class="chip bg-emerald-50 text-emerald-700">{{ __('Completed') }}</span>
                  @elseif($lessonState)
                    <span class="chip bg-amber-50 text-amber-700">{{ __('In progress') }}</span>
                  @endif
                  @if($enrollment)
                    <a href="{{ route('learning.lessons.show', $lesson) }}" class="btn-outline">{{ __('Open lesson') }}</a>
                  @else
                    <span class="text-xs text-navy-400">{{ __('Enroll to unlock') }}</span>
                  @endif
                </div>
              </li>
            @endforeach
          </ol>
        </section>
      @endif
    @endforeach
  </div>
</section>
</x-layout>
