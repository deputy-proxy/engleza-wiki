<x-layout :title="'Class results — '.$group->name">
<section class="mx-auto max-w-6xl px-6 py-12">
  <div class="flex items-center justify-between gap-6 flex-wrap">
    <div>
      <div class="text-xs font-semibold uppercase tracking-widest text-brand-600">Class results</div>
      <h1 class="mt-2 text-3xl font-extrabold text-navy-900">{{ $group->name }}</h1>
      <div class="mt-1 text-navy-600">Join code: <span class="font-mono font-bold text-navy-900">{{ $group->join_code }}</span></div>
    </div>
    <a href="{{ route('classes.show', $group->join_code) }}" class="btn-outline">Student view →</a>
  </div>

  <div class="mt-8 grid md:grid-cols-3 gap-6">
    <div class="card p-6"><div class="text-sm text-navy-500">Attempts</div><div class="mt-2 text-4xl font-extrabold text-navy-900" data-testid="class-stat-attempts">{{ $attempts->count() }}</div></div>
    <div class="card p-6"><div class="text-sm text-navy-500">Completed</div><div class="mt-2 text-4xl font-extrabold text-navy-900" data-testid="class-stat-completed">{{ $completed->count() }}</div></div>
    <div class="card p-6"><div class="text-sm text-navy-500">Average score</div><div class="mt-2 text-4xl font-extrabold text-brand-600" data-testid="class-stat-avg">{{ $avg }}%</div></div>
  </div>

  @if($levels->count())
    <h2 class="mt-10 text-xl font-bold text-navy-900">Level distribution</h2>
    <div class="mt-4 space-y-2" data-testid="class-levels">
      @foreach($levels->sortKeys() as $lvl => $count)
        <div>
          <div class="flex justify-between text-sm mb-1"><span class="font-semibold text-navy-900">{{ $lvl ?? '—' }}</span><span class="text-navy-600">{{ $count }} learner(s)</span></div>
          <div class="h-2 rounded-full bg-navy-100 overflow-hidden"><div class="h-full bg-brand-500" style="width: {{ round(($count/$completed->count())*100) }}%"></div></div>
        </div>
      @endforeach
    </div>
  @endif

  <h2 class="mt-10 text-xl font-bold text-navy-900">All attempts</h2>
  <div class="mt-4 card overflow-x-auto">
    <table class="w-full text-sm" data-testid="class-attempts-table">
      <thead class="bg-navy-50 text-left text-navy-700"><tr>
        <th class="px-4 py-3">Learner</th><th class="px-4 py-3">Test</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Score</th><th class="px-4 py-3">Level</th><th class="px-4 py-3">Date</th>
      </tr></thead>
      <tbody class="divide-y divide-navy-50">
        @forelse($attempts as $a)
          <tr>
            <td class="px-4 py-3 text-navy-900 font-medium">{{ $a->user?->name ?? 'Anonymous' }}</td>
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
