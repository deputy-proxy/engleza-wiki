<x-layout :title="'Class results — '.$group->name">
<section class="mx-auto max-w-6xl px-6 py-12">
  <div class="flex flex-wrap items-center justify-between gap-6">
    <div>
      <div class="text-xs font-semibold uppercase tracking-widest text-brand-600">Class results</div>
      <h1 class="mt-2 text-3xl font-extrabold text-navy-900">{{ $group->name }}</h1>
      <div class="mt-1 text-navy-600">Join code: <span class="font-mono font-bold text-navy-900">{{ $group->join_code }}</span></div>
    </div>
    <a href="{{ route('classes.show', $group->join_code) }}" class="btn-outline">Student view →</a>
  </div>

  <div class="mt-8 grid gap-6 md:grid-cols-3">
    <div class="card p-6"><div class="text-sm text-navy-500">Attempts</div><div class="mt-2 text-4xl font-extrabold text-navy-900" data-testid="class-stat-attempts">{{ $attempts->count() }}</div></div>
    <div class="card p-6"><div class="text-sm text-navy-500">Completed</div><div class="mt-2 text-4xl font-extrabold text-navy-900" data-testid="class-stat-completed">{{ $completed->count() }}</div></div>
    <div class="card p-6"><div class="text-sm text-navy-500">Average score</div><div class="mt-2 text-4xl font-extrabold text-brand-600" data-testid="class-stat-avg">{{ $avg }}%</div></div>
  </div>

  @if($levels->count())
    <h2 class="mt-10 text-xl font-bold text-navy-900">Level distribution</h2>
    <div class="mt-4 space-y-2" data-testid="class-levels">
      @foreach($levels->sortKeys() as $lvl => $count)
        <div>
          <div class="mb-1 flex justify-between text-sm"><span class="font-semibold text-navy-900">{{ $lvl ?? '—' }}</span><span class="text-navy-600">{{ $count }} learner(s)</span></div>
          <div class="h-2 overflow-hidden rounded-full bg-navy-100"><div class="h-full bg-brand-500" style="width: {{ round(($count/$completed->count())*100) }}%"></div></div>
        </div>
      @endforeach
    </div>
  @endif

  <h2 class="mt-10 text-xl font-bold text-navy-900">Learner progress by skill and topic</h2>
  <p class="mt-1 text-sm text-navy-600">This summary uses only completed attempts submitted to this class group.</p>
  <div class="mt-4 grid gap-4 md:grid-cols-2">
    @forelse($learnerProgress as $learner)
      <article class="card p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="font-bold text-navy-900">{{ $learner['user']?->name ?? 'Learner' }}</h3>
            <p class="text-sm text-navy-500">{{ $learner['attempt_count'] }} completed attempt(s) · {{ $learner['question_count'] }} distinct question(s)</p>
          </div>
          <strong class="text-xl text-navy-900">{{ $learner['percentage'] === null ? '—' : number_format($learner['percentage'], 1).'%' }}</strong>
        </div>
        @if($learner['skills'])
          <h4 class="mt-4 text-xs font-semibold uppercase tracking-wide text-navy-500">Skills</h4>
          <div class="mt-2 flex flex-wrap gap-2">
            @foreach($learner['skills'] as $skill)
              <span class="chip">{{ $skill['name'] }} · {{ number_format($skill['percentage'], 1) }}%</span>
            @endforeach
          </div>
        @endif
        @if($learner['topics'])
          <h4 class="mt-4 text-xs font-semibold uppercase tracking-wide text-navy-500">Topics</h4>
          <div class="mt-2 flex flex-wrap gap-2">
            @foreach($learner['topics'] as $topic)
              <span class="chip">{{ $topic['name'] }} · {{ number_format($topic['percentage'], 1) }}%</span>
            @endforeach
          </div>
        @endif
      </article>
    @empty
      <p class="card p-5 text-sm text-navy-600">Learner progress summaries will appear after registered learners complete class-group assessments.</p>
    @endforelse
  </div>

  <h2 class="mt-10 text-xl font-bold text-navy-900">All attempts</h2>
  <div class="card mt-4 overflow-x-auto">
    <table class="w-full text-sm" data-testid="class-attempts-table">
      <thead class="bg-navy-50 text-left text-navy-700"><tr>
        <th class="px-4 py-3">Learner</th><th class="px-4 py-3">Test</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Score</th><th class="px-4 py-3">Level</th><th class="px-4 py-3">Date</th>
      </tr></thead>
      <tbody class="divide-y divide-navy-50">
        @forelse($attempts as $a)
          <tr>
            <td class="px-4 py-3 font-medium text-navy-900">{{ $a->user?->name ?? 'Anonymous' }}</td>
            <td class="px-4 py-3">{{ $a->test->title }}</td>
            <td class="px-4 py-3">
              @if($a->status==='completed')<span class="chip bg-emerald-50 text-emerald-700">Completed</span>
              @else<span class="chip">{{ ucfirst($a->status) }}</span>@endif
            </td>
            <td class="px-4 py-3">{{ $a->percentage ? $a->percentage.'%' : '—' }}</td>
            <td class="px-4 py-3">{{ $a->level_result ?? '—' }}</td>
            <td class="px-4 py-3 text-navy-600">{{ $a->created_at->format('M d, Y H:i') }}</td>
          </tr>
        @empty<tr><td class="px-4 py-8 text-center text-navy-500" colspan="6">No attempts yet. Share the join code <strong class="text-navy-900">{{ $group->join_code }}</strong> with your class.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
</x-layout>
