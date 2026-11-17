<x-layout title="Vocabulary review · engleza.wiki" description="Review vocabulary with a spaced repetition schedule.">
<section class="mx-auto max-w-5xl px-6 py-12">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-3xl font-extrabold text-navy-900">Vocabulary review</h1>
      <p class="mt-2 text-navy-600">Review items when they are due. Your schedule adapts to each answer.</p>
    </div>
    <div class="card px-5 py-3 text-right">
      <div class="text-sm text-navy-500">Due now</div>
      <div class="text-3xl font-extrabold text-navy-900">{{ $dueCount }}</div>
    </div>
  </div>

  @if(session('reviewed'))
    <p class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">Review saved. Your next review date has been updated.</p>
  @endif

  <h2 class="mt-10 text-xl font-bold text-navy-900">Ready to review</h2>
  @forelse($dueItems as $item)
    <article class="card mt-4 p-6" x-data="{ revealed: false }">
      <p class="text-xs font-semibold uppercase tracking-wide text-navy-500">Vocabulary item</p>
      <h3 class="mt-2 text-xl font-bold text-navy-900">{{ $item->prompt }}</h3>
      <div class="mt-4">
        <button type="button" class="btn-outline" @click="revealed = ! revealed" :aria-expanded="revealed.toString()">Reveal answer</button>
        <p x-cloak x-show="revealed" class="mt-3 rounded-lg bg-navy-50 p-4 text-navy-800">{{ $item->answer }}</p>
      </div>
      <form method="POST" action="{{ route('reviews.answer', $item) }}" class="mt-5 flex flex-wrap gap-2">
        @csrf
        <button name="grade" value="again" class="btn-outline" type="submit">Again</button>
        <button name="grade" value="hard" class="btn-outline" type="submit">Hard</button>
        <button name="grade" value="good" class="btn-primary" type="submit">Good</button>
        <button name="grade" value="easy" class="btn-outline" type="submit">Easy</button>
      </form>
    </article>
  @empty
    <div class="card mt-4 p-6">
      <p class="font-semibold text-navy-900">You are all caught up.</p>
      <p class="mt-1 text-sm text-navy-600">New vocabulary items will appear here as you explore published flashcards and vocabulary lessons.</p>
    </div>
  @endforelse

  <h2 class="mt-10 text-xl font-bold text-navy-900">Coming up in the next 7 days</h2>
  <div class="mt-4 divide-y divide-navy-100 card">
    @forelse($upcomingItems as $item)
      <div class="flex items-center justify-between gap-4 px-5 py-4">
        <div><p class="font-medium text-navy-900">{{ $item->prompt }}</p><p class="text-sm text-navy-500">{{ $item->repetitions }} successful repetitions</p></div>
        <time class="shrink-0 text-sm text-navy-600" datetime="{{ $item->due_at->toIso8601String() }}">{{ $item->due_at->diffForHumans() }}</time>
      </div>
    @empty
      <p class="px-5 py-4 text-sm text-navy-600">No upcoming reviews scheduled.</p>
    @endforelse
  </div>
</section>
</x-layout>
