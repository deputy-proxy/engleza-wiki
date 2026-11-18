<x-layout :title="__('Dashboard – engleza.wiki')">
<section class="mx-auto max-w-6xl px-6 py-12">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-3xl font-extrabold text-navy-900">{{ __('Welcome, ') }}{{ auth()->user()->name }}</h1>
      <p class="text-navy-600 mt-1">{{ __('Your English progress at a glance.') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="{{ route('dashboard.learning') }}" class="btn-outline">{{ __('My learning') }}</a>
      <a href="{{ route('reviews.index') }}" class="btn-outline">{{ __('Vocabulary review') }}</a>
      <form method="POST" action="{{ route('practice.start') }}">@csrf<button type="submit" class="btn-primary">{{ __('Start adaptive practice') }}</button></form>
    </div>
  </div>

  <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="card p-5"><div class="text-sm text-navy-500">{{ __('Tests completed') }}</div><div class="mt-2 text-3xl font-extrabold text-navy-900" data-testid="stat-tests">{{ $completed->count() }}</div></div>
    <div class="card p-5"><div class="text-sm text-navy-500">{{ __('Recent average') }}</div><div class="mt-2 text-3xl font-extrabold text-navy-900" data-testid="stat-avg">{{ $avg }}%</div></div>
    <div class="card p-5"><div class="text-sm text-navy-500">{{ __('Latest CEFR assessment') }}</div><div class="mt-2 text-3xl font-extrabold text-brand-600" data-testid="stat-level">{{ $progress['cefr']['level'] ?? $latestLevel ?? '—' }}</div>
      @if($progress['cefr']['assessed_at'])<p class="mt-1 text-xs text-navy-500">{{ __('Assessed') }} {{ $progress['cefr']['assessed_at']->diffForHumans() }}</p>@endif
    </div>
    <div class="card p-5"><div class="text-sm text-navy-500">{{ __('Vocabulary reviews due') }}</div><div class="mt-2 text-3xl font-extrabold text-navy-900">{{ $progress['stats']['reviews_due'] }}</div><a href="{{ route('reviews.index') }}" class="mt-1 inline-block text-sm font-semibold text-brand-600">{{ __('Review now →') }}</a></div>
  </div>

  <section class="mt-8" aria-labelledby="engagement-heading">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 id="engagement-heading" class="text-2xl font-bold text-navy-900">Your learning momentum</h2>
        <p class="mt-1 text-sm text-navy-600">Small, consistent sessions add up. XP rewards learning activity, not test scores.</p>
      </div>
      @if(session('engagement_settings_updated'))
        <p role="status" class="text-sm font-semibold text-emerald-700">Your daily goal was updated.</p>
      @endif
    </div>
    <div class="mt-4 grid gap-4 md:grid-cols-3">
      <article class="card p-5">
        <p class="text-sm text-navy-500">Total XP</p>
        <p class="mt-2 text-3xl font-extrabold text-navy-900" data-testid="engagement-xp">{{ number_format($engagement['xp_total']) }}</p>
        <p class="mt-1 text-xs text-navy-500">Earned from completed learning activities</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-navy-500">Current streak</p>
        <p class="mt-2 text-3xl font-extrabold text-navy-900" data-testid="engagement-streak">{{ $engagement['current_streak'] }} {{ $engagement['current_streak'] === 1 ? 'day' : 'days' }}</p>
        <p class="mt-1 text-xs text-navy-500">Consecutive days you reached your goal</p>
      </article>
      <article class="card p-5">
        <div class="flex items-center justify-between gap-3">
          <p class="text-sm text-navy-500">Today's goal</p>
          <strong class="text-sm text-navy-900">{{ $engagement['daily_goal']['earned'] }} / {{ $engagement['daily_goal']['target'] }} XP</strong>
        </div>
        <div class="mt-3 h-3 overflow-hidden rounded-full bg-navy-100" role="progressbar" aria-label="Daily XP goal" aria-valuemin="0" aria-valuemax="{{ $engagement['daily_goal']['target'] }}" aria-valuenow="{{ min($engagement['daily_goal']['earned'], $engagement['daily_goal']['target']) }}">
          <div class="h-full rounded-full bg-brand-500" style="width: {{ $engagement['daily_goal']['percentage'] }}%"></div>
        </div>
        <p class="mt-2 text-xs text-navy-500">{{ $engagement['daily_goal']['completed'] ? 'Daily goal completed. Keep going if you want.' : $engagement['daily_goal']['remaining'].' XP to reach today’s goal.' }}</p>
      </article>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
      <form method="POST" action="{{ route('engagement.goal.update') }}" class="card p-5">
        @csrf
        @method('PUT')
        <h3 class="font-bold text-navy-900">Your daily goal</h3>
        <p class="mt-1 text-sm text-navy-600">Choose a target that fits your routine.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <label class="block text-sm font-semibold text-navy-700">Daily target
            <select name="daily_goal_xp" class="mt-1 w-full rounded-lg border border-navy-200 px-3 py-2" required>
              @foreach([10, 20, 30, 50] as $goal)
                <option value="{{ $goal }}" @selected($engagement['daily_goal_xp'] === $goal)>{{ $goal }} XP</option>
              @endforeach
            </select>
          </label>
          <label class="block text-sm font-semibold text-navy-700">Timezone
            <input name="timezone" value="{{ $engagement['timezone'] }}" list="engagement-timezones" class="mt-1 w-full rounded-lg border border-navy-200 px-3 py-2" maxlength="64" required>
            <datalist id="engagement-timezones"><option value="Europe/Bucharest"><option value="Europe/London"><option value="Europe/Paris"><option value="America/New_York"><option value="UTC"></datalist>
          </label>
        </div>
        @error('daily_goal_xp')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('timezone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        <button type="submit" class="btn-primary mt-4">Save goal</button>
        <p class="mt-3 text-xs text-navy-500">Activity dates are saved in this timezone. Changing it affects future activity, not your recorded history.</p>
      </form>

      <section class="card p-5" aria-labelledby="xp-history-heading">
        <h3 id="xp-history-heading" class="font-bold text-navy-900">Recent XP awards</h3>
        <div class="mt-3 divide-y divide-navy-100">
          @forelse($engagement['recent_awards'] as $award)
            <div class="flex items-center justify-between gap-3 py-3">
              <div>
                <p class="text-sm font-semibold text-navy-900">{{ match($award->source_type) { 'lesson' => 'Lesson completed', 'adaptive_practice' => 'Adaptive practice', 'vocabulary_review' => 'Vocabulary review', default => 'Learning activity' } }}</p>
                <p class="text-xs text-navy-500">{{ $award->occurred_at->diffForHumans() }}</p>
              </div>
              <span class="font-bold text-brand-600">+{{ $award->points }} XP</span>
            </div>
          @empty
            <p class="py-4 text-sm text-navy-600">Complete a lesson, practice session or vocabulary review to earn your first XP.</p>
          @endforelse
        </div>
      </section>
    </div>

    <section class="card mt-4 p-5" aria-labelledby="achievements-heading">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 id="achievements-heading" class="font-bold text-navy-900">Achievements</h3>
        <span class="text-xs text-navy-500">{{ $engagement['achievements']->count() }} of {{ count($engagement['available_achievements']) }} unlocked</span>
      </div>
      @php($earnedAchievements = $engagement['achievements']->keyBy('achievement_key'))
      <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($engagement['available_achievements'] as $key => $achievement)
          @php($unlocked = $earnedAchievements->get($key))
          <article class="rounded-xl border p-3 {{ $unlocked ? 'border-brand-200 bg-brand-50' : 'border-navy-100 bg-white' }}">
            <p class="text-sm font-bold {{ $unlocked ? 'text-navy-900' : 'text-navy-500' }}">{{ $achievement['title'] }}</p>
            <p class="mt-1 text-xs text-navy-500">{{ $achievement['description'] }}</p>
            @if($unlocked)<p class="mt-2 text-xs font-semibold text-brand-700">Unlocked {{ $unlocked->awarded_at->diffForHumans() }}</p>@else<p class="mt-2 text-xs text-navy-400">Not yet unlocked</p>@endif
          </article>
        @endforeach
      </div>
    </section>
    <p class="mt-3 text-xs text-navy-500">Leaderboards are intentionally disabled until there is a sufficiently active cohort and a clear consent and privacy model.</p>
  </section>

  @if(count($progress['learning_paths']))
    <section class="mt-10">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-2xl font-bold text-navy-900">{{ __('Your learning paths') }}</h2>
        <a href="{{ route('dashboard.learning') }}" class="text-sm font-semibold text-brand-600">{{ __('View all learning →') }}</a>
      </div>
      <div class="mt-4 grid gap-4 md:grid-cols-2">
        @foreach($progress['learning_paths'] as $path)
          <article class="card p-5">
            <div class="flex items-start justify-between gap-3">
              <div>
                @if($path['level'])<span class="chip">{{ $path['level'] }}</span>@endif
                <h3 class="mt-2 font-bold text-navy-900">{{ $path['title'] }}</h3>
              </div>
              <span class="text-sm font-semibold text-navy-700">{{ number_format($path['percentage'], 0) }}%</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-navy-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ $path['percentage'] }}%"></div></div>
            <p class="mt-2 text-xs text-navy-500">{{ $path['completed_lessons'] }} {{ __('of') }} {{ $path['available_lessons'] }} {{ __('lessons completed') }} · {{ __('Completion is activity, not mastery.') }}</p>
            @if($path['next_lesson'])
              <a href="{{ $path['next_lesson']['url'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600">{{ __('Continue: ') }}{{ $path['next_lesson']['title'] }} →</a>
              @if(count($path['next_lesson']['objectives']))
                <ul class="mt-2 list-disc pl-5 text-xs text-navy-600">
                  @foreach(array_slice($path['next_lesson']['objectives'], 0, 2) as $objective)<li>{{ $objective }}</li>@endforeach
                </ul>
              @endif
            @else
              <a href="{{ $path['url'] }}" class="mt-4 inline-flex text-sm font-semibold text-brand-600">{{ __('Review path →') }}</a>
            @endif
          </article>
        @endforeach
      </div>
    </section>
  @endif

  <div class="mt-10 grid gap-8 lg:grid-cols-2">
    <section>
      <div class="flex items-center justify-between gap-3">
        <h2 class="text-2xl font-bold text-navy-900">{{ __('Progress by skill') }}</h2>
        <span class="text-xs text-navy-500">{{ __('Evidence-based mastery') }}</span>
      </div>
      <div class="card mt-4 divide-y divide-navy-100">
        @forelse($progress['skills'] as $skill)
          <div class="p-4">
            <div class="flex items-center justify-between gap-3">
              <span class="font-semibold text-navy-900">{{ $skill['name'] }}</span>
              @if($skill['mastery_percentage'] !== null)
                <div class="flex items-center gap-2"><span class="text-xs font-semibold text-navy-500">{{ $skill['mastery_state'] === 'needs_practice' ? __('Needs practice') : __($skill['mastery_state']) }}</span><strong class="text-navy-900">{{ number_format($skill['mastery_percentage'], 1) }}%</strong></div>
              @else
                <span class="text-xs font-semibold text-navy-500">{{ __('Not enough evidence') }}</span>
              @endif
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-navy-100">
              <div class="h-full rounded-full bg-brand-500" style="width: {{ $skill['mastery_percentage'] ?? 0 }}%"></div>
            </div>
            <p class="mt-1 text-xs text-navy-500">{{ $skill['unique_questions'] }} {{ __('distinct questions') }} · {{ $skill['attempt_count'] }} {{ __('attempts') }} · {{ __(ucfirst($skill['confidence'])) }} {{ __('confidence') }}</p>
          </div>
        @empty
          <p class="p-5 text-sm text-navy-600">{{ __('Complete a test or interactive exercise to start building your skill profile.') }}</p>
        @endforelse
      </div>
    </section>

    <section>
      <h2 class="text-2xl font-bold text-navy-900">{{ __('Progress by topic') }}</h2>
      <div class="card mt-4 divide-y divide-navy-100">
        @forelse($progress['topics'] as $topic)
          <div class="p-4">
            <div class="flex items-center justify-between gap-3">
              <span class="font-semibold text-navy-900">{{ $topic['name'] }}</span>
              @if($topic['mastery_percentage'] !== null)
                <div class="flex items-center gap-2"><span class="text-xs font-semibold text-navy-500">{{ $topic['mastery_state'] === 'needs_practice' ? __('Needs practice') : __($topic['mastery_state']) }}</span><strong class="text-navy-900">{{ number_format($topic['mastery_percentage'], 1) }}%</strong></div>
              @else
                <span class="text-xs font-semibold text-navy-500">{{ __('Not enough evidence') }}</span>
              @endif
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-navy-100">
              <div class="h-full rounded-full bg-brand-500" style="width: {{ $topic['mastery_percentage'] ?? 0 }}%"></div>
            </div>
            <p class="mt-1 text-xs text-navy-500">{{ $topic['unique_questions'] }} {{ __('distinct questions') }} · {{ $topic['attempt_count'] }} {{ __('attempts') }} · {{ __(ucfirst($topic['confidence'])) }} {{ __('confidence') }}</p>
          </div>
        @empty
          <p class="p-5 text-sm text-navy-600">{{ __('Topic mastery appears as you answer tagged questions.') }}</p>
        @endforelse
      </div>
    </section>
  </div>

  <div class="mt-10 grid gap-8 lg:grid-cols-2">
    <section>
      <h2 class="text-2xl font-bold text-navy-900">{{ __('Recommended next steps') }}</h2>
      <div class="mt-4 space-y-3">
        @forelse($progress['recommendations'] as $recommendation)
          <a href="{{ $recommendation['url'] }}" class="card block p-5 hover:shadow-lg">
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-600">{{ $recommendation['type'] === 'topic' ? __('Practise a topic') : ($recommendation['type'] === 'review' ? __('Spaced review') : __('Learning path')) }}</span>
            <h3 class="mt-1 font-bold text-navy-900">{{ $recommendation['title'] }}</h3>
            <p class="mt-1 text-sm text-navy-600">{{ $recommendation['reason'] }}</p>
          </a>
        @empty
          <div class="card p-5"><p class="text-sm text-navy-600">{{ __('Your next steps will appear here as you build a learning history.') }}</p></div>
        @endforelse
      </div>
    </section>
    <section>
      <h2 class="text-2xl font-bold text-navy-900">{{ __('Recent learning activity') }}</h2>
      <div class="card mt-4 divide-y divide-navy-100">
        @forelse($progress['recent_activity'] as $activity)
          <div class="flex items-center justify-between gap-4 p-4">
            <div>
              @if($activity['url'])<a href="{{ $activity['url'] }}" class="font-semibold text-navy-900 hover:text-brand-600">{{ $activity['title'] }}</a>@else<span class="font-semibold text-navy-900">{{ $activity['title'] }}</span>@endif
              <p class="text-sm text-navy-600">{{ $activity['detail'] }}</p>
            </div>
            <time class="shrink-0 text-xs text-navy-500">{{ $activity['date']?->diffForHumans() }}</time>
          </div>
        @empty
          <p class="p-5 text-sm text-navy-600">{{ __('Your completed lessons and exercises will appear here.') }}</p>
        @endforelse
      </div>
    </section>
  </div>

  <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('Recent tests') }}</h2>
  <div class="mt-4 space-y-3">
    @forelse($attempts as $a)
      <a href="{{ route('tests.result',['test'=>$a->test->slug,'attempt'=>$a->id]) }}" class="card p-5 flex items-center justify-between hover:shadow-lg">
        <div><div class="font-bold text-navy-900">{{ $a->test->translated('title') }}</div><div class="text-sm text-navy-500">{{ $a->created_at->diffForHumans() }}</div></div>
        <div class="text-right"><div class="font-bold text-navy-900">{{ $a->percentage ?? 0 }}%</div><div class="text-sm text-brand-600">{{ $a->level_result ?? __(ucfirst($a->status)) }}</div></div>
      </a>
    @empty <p class="text-navy-600">{{ __('No tests yet. ') }}<a class="text-brand-600 font-semibold" href="{{ route('tests.index') }}">{{ __('Take one →') }}</a></p>
    @endforelse
  </div>

  <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('Saved resources') }}</h2>
  <div class="mt-4 grid gap-4 md:grid-cols-2">
    @forelse($saved as $s)
      <a href="{{ route('resources.show',$s->resource->slug) }}" class="card p-5 hover:shadow-lg"><div class="font-bold text-navy-900">{{ $s->resource->translated('title') }}</div></a>
    @empty <p class="text-navy-600">{{ __('Nothing saved yet.') }}</p>
    @endforelse
  </div>
</section>
</x-layout>
