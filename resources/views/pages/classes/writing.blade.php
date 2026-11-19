<x-layout :robots-index="false" :title="__('Writing submissions — ').$group->name">
<section class="mx-auto max-w-6xl px-6 py-12">
  <a href="{{ route('classes.results', $group) }}" class="text-sm font-semibold text-brand-700">← {{ __('Class results') }}</a>
  <h1 class="mt-3 text-3xl font-extrabold text-navy-900">{{ __('Writing submissions') }}</h1>
  <p class="mt-2 text-navy-600">{{ $group->name }} · {{ __('Only submissions made through this class assignment are shown.') }}</p>
  <div class="mt-8 space-y-3">
    @forelse($submissions as $submission)
      <a href="{{ route('classes.writing.show', [$group, $submission]) }}" class="card flex flex-wrap items-center justify-between gap-4 p-5 hover:shadow-lg" data-testid="teacher-writing-{{ $submission->id }}">
        <div><div class="font-bold text-navy-900">{{ $submission->user?->name ?? __('Learner') }}</div><div class="text-sm text-navy-600">{{ $submission->writingResource->resource->translated('title') }} · {{ $submission->word_count }} {{ __('words') }}</div></div>
        <div class="text-right"><span class="chip">{{ __(str_replace('_', ' ', ucfirst($submission->status))) }}</span><div class="mt-1 text-xs text-navy-500">{{ $submission->updated_at->translatedFormat('j M Y, H:i') }}</div></div>
      </a>
    @empty
      <p class="card p-6 text-navy-600">{{ __('No writing submissions have been received for this class yet.') }}</p>
    @endforelse
  </div>
  <div class="mt-6">{{ $submissions->links() }}</div>
</section>
</x-layout>
