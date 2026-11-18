<x-layout title="{{ __('Learning paths · engleza.wiki') }}" description="{{ __('Structured English learning paths with lessons, exercises and assessments.') }}">
<section class="mx-auto max-w-6xl px-6 py-12">
  <p class="text-sm font-semibold uppercase tracking-widest text-brand-600">{{ __('Learn with a plan') }}</p>
  <h1 class="mt-3 text-4xl font-extrabold text-navy-900">{{ __('Learning paths') }}</h1>
  <p class="mt-3 max-w-2xl text-lg text-navy-600">{{ __('Follow a clear sequence of lessons, practice activities and assessments. Pick up where you left off.') }}</p>
  @if($paths->isEmpty())
    <div class="mt-10 card p-8 text-navy-600">{{ __('No learning paths are currently available.') }}</div>
  @else
    <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      @foreach($paths as $path)
        @php $lessonCount = $path->units->where('is_published', true)->sum(fn ($unit) => $unit->lessons->filter(fn ($lesson) => app(\App\Services\Learning\LearningPathService::class)->isLessonAvailable($lesson))->count()); @endphp
        <a href="{{ route('learning.paths.show', $path) }}" class="card p-6 transition hover:border-brand-300 hover:shadow-lg">
          @if($path->level)<span class="chip bg-brand-50 text-brand-700">{{ $path->level->translated('name') }}</span>@endif
          <h2 class="mt-4 text-xl font-bold text-navy-900">{{ $path->translated('title') }}</h2>
          <p class="mt-2 text-sm text-navy-600 line-clamp-3">{{ $path->translated('description') }}</p>
          <div class="mt-5 flex flex-wrap gap-2">
            @foreach($path->skills->take(3) as $skill)<span class="chip">{{ $skill->translated('name') }}</span>@endforeach
          </div>
          <p class="mt-5 text-sm font-semibold text-brand-600">{{ $lessonCount }} {{ __('lessons') }} · {{ __('View path →') }}</p>
        </a>
      @endforeach
    </div>
  @endif
</section>
</x-layout>
