<x-layout :title="'My Writing – engleza.wiki'">
<section class="mx-auto max-w-6xl px-6 py-12">
  <h1 class="text-3xl font-extrabold text-navy-900">My Writing</h1>
  <p class="mt-2 text-navy-600">All your writing drafts and submissions.</p>
  <div class="mt-8 space-y-3">
    @forelse($submissions as $s)
      <a href="{{ route('resources.show',$s->writingResource->resource->slug) }}" class="card p-5 flex items-center justify-between hover:shadow-lg" data-testid="writing-item-{{ $s->id }}">
        <div><div class="font-bold text-navy-900">{{ $s->writingResource->resource->title }}</div><div class="text-sm text-navy-500">{{ $s->created_at->format('M d, Y') }}</div></div>
        <div class="text-right"><div class="font-bold">{{ $s->word_count }} words</div><div class="text-sm text-brand-600">{{ ucfirst($s->status) }}</div></div>
      </a>
    @empty <p class="text-navy-600">You have no writing submissions yet.</p>
    @endforelse
  </div>
  <div class="mt-6">{{ $submissions->links() }}</div>
</section>
</x-layout>
