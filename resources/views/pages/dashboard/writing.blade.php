<x-layout :robots-index="false" :title="__('My Writing – engleza.wiki')">
<section class="mx-auto max-w-6xl px-6 py-12">
  <h1 class="text-3xl font-extrabold text-navy-900">{{ __('My Writing') }}</h1>
  <p class="mt-2 text-navy-600">{{ __('All your writing drafts and submissions.') }}</p>
  <div class="mt-8 space-y-3">
    @forelse($submissions as $s)
      <a href="{{ route('resources.show',$s->writingResource->resource->slug) }}" class="card p-5 flex items-center justify-between hover:shadow-lg" data-testid="writing-item-{{ $s->id }}">
        <div><div class="font-bold text-navy-900">{{ $s->writingResource->resource->translated('title') }}</div><div class="text-sm text-navy-500">{{ $s->created_at->translatedFormat('j M Y') }}</div></div>
        <div class="text-right"><div class="font-bold">{{ $s->word_count }} {{ __('words') }}</div><div class="text-sm text-brand-600">{{ __(str_replace('_', ' ', ucfirst($s->status))) }}</div></div>
      </a>
    @empty <p class="text-navy-600">{{ __('You have no writing submissions yet.') }}</p>
    @endforelse
  </div>
  <div class="mt-6">{{ $submissions->links() }}</div>
</section>
</x-layout>
