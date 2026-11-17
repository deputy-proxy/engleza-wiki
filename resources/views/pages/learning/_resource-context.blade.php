@if(isset($learningActivities) && $learningActivities->isNotEmpty())
  <aside class="mt-8 rounded-2xl border border-brand-100 bg-brand-50 p-5" data-testid="resource-learning-context">
    <p class="text-xs font-semibold uppercase tracking-widest text-brand-700">Part of a learning path</p>
    <ul class="mt-3 space-y-2">
      @foreach($learningActivities as $activity)
        <li>
          <a href="{{ route('learning.lessons.show', $activity->lesson) }}" class="font-semibold text-navy-900 hover:text-brand-700">
            {{ $activity->lesson->title }} <span class="text-brand-600">→</span>
          </a>
          <p class="text-sm text-navy-600">{{ $activity->lesson->unit->path->title }}</p>
        </li>
      @endforeach
    </ul>
  </aside>
@endif
