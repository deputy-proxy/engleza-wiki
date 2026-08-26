<x-layout :title="'My tests'">
<section class="mx-auto max-w-6xl px-6 py-12">
  <h1 class="text-3xl font-extrabold text-navy-900">My tests</h1>
  <div class="mt-6 space-y-3">
    @foreach($attempts as $a)
      <a href="{{ route('tests.result',['test'=>$a->test->slug,'attempt'=>$a->id]) }}" class="card p-5 flex items-center justify-between hover:shadow-lg">
        <div><div class="font-bold text-navy-900">{{ $a->test->title }}</div><div class="text-sm text-navy-500">{{ $a->created_at->format('M d, Y') }}</div></div>
        <div class="text-right"><div class="font-bold">{{ $a->percentage ?? 0 }}%</div><div class="text-sm text-brand-600">{{ $a->level_result ?? $a->status }}</div></div>
      </a>
    @endforeach
  </div>
  <div class="mt-6">{{ $attempts->links() }}</div>
</section>
</x-layout>
