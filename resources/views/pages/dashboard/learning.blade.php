<x-layout :title="__('My learning · engleza.wiki')" :description="__('Continue your enrolled learning paths.')">
<section class="mx-auto max-w-5xl px-6 py-12">
  <h1 class="text-3xl font-extrabold text-navy-900">{{ __('My learning') }}</h1>
  <p class="mt-2 text-navy-600">{{ __('Your learning paths, progress and next lessons.') }}</p>
  @if($enrollments->isEmpty())
    <div class="mt-8 card p-8">
      <p class="text-navy-700">{{ __('You have not enrolled in a learning path yet.') }}</p>
      <a href="{{ route('learning.paths.index') }}" class="btn-primary mt-5 inline-flex">{{ __('Explore learning paths') }}</a>
    </div>
  @else
    <div class="mt-8 space-y-5">
      @foreach($enrollments as $enrollment)
        <article class="card p-6">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              @if($enrollment->path->level)<span class="chip bg-brand-50 text-brand-700">{{ $enrollment->path->level->translated('name') }}</span>@endif
              <h2 class="mt-2 text-xl font-bold text-navy-900">{{ $enrollment->path->translated('title') }}</h2>
              <p class="mt-1 text-sm text-navy-600">{{ $enrollment->path->translated('description') }}</p>
            </div>
            <span class="chip">{{ __(ucfirst($enrollment->status)) }}</span>
          </div>
          <div class="mt-5 flex items-center justify-between text-sm text-navy-600">
            <span>{{ __('Progress') }}</span>
            <strong>{{ $enrollment->completed_lesson_count }} / {{ $enrollment->available_lesson_count }} {{ __('lessons') }}</strong>
          </div>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-navy-100">
            <div class="h-full bg-brand-500" style="width: {{ $enrollment->available_lesson_count ? round($enrollment->completed_lesson_count / $enrollment->available_lesson_count * 100) : 0 }}%"></div>
          </div>
          @if($enrollment->path->status !== 'published')
            <p class="mt-4 text-sm text-navy-500">{{ __('This learning path is no longer available.') }}</p>
          @elseif($enrollment->available_lesson_count === 0)
            <p class="mt-4 text-sm text-navy-500">{{ __('No lessons are currently available in this path.') }}</p>
          @elseif($enrollment->nextLesson)
            <p class="mt-4 text-sm text-navy-600">{{ __('Continue from: ') }}<strong class="text-navy-900">{{ $enrollment->nextLesson->translated('title') }}</strong></p>
            <a href="{{ route('learning.lessons.show', $enrollment->nextLesson) }}" class="btn-primary mt-5 inline-flex">{{ __('Continue learning') }}</a>
          @else
            <p class="mt-4 text-sm font-semibold text-emerald-700">{{ __('You have completed all currently available lessons.') }}</p>
            <a href="{{ route('learning.paths.show', $enrollment->path) }}" class="btn-outline mt-5 inline-flex">{{ __('Review learning path') }}</a>
          @endif
        </article>
      @endforeach
    </div>
  @endif
</section>
</x-layout>
