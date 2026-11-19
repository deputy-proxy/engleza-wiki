<x-layout :robots-index="false" :title="__('Writing submission — engleza.wiki')">
<section class="mx-auto max-w-4xl px-6 py-12">
  <a href="{{ route('dashboard.writing') }}" class="text-sm font-semibold text-brand-700">← {{ __('My Writing') }}</a>
  <div class="mt-4 flex flex-wrap items-start justify-between gap-4"><div><h1 class="text-3xl font-extrabold text-navy-900">{{ $submission->writingResource->resource->translated('title') }}</h1><p class="mt-2 text-sm text-navy-500">{{ __('Version') }} {{ $submission->versions->last()?->version_number ?? 1 }} · {{ $submission->word_count }} {{ __('words') }}</p></div><span class="chip">{{ __(str_replace('_', ' ', ucfirst($submission->status))) }}</span></div>
  @if(session('status') === 'writing-saved')<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ __('Your writing has been saved.') }}</p>@endif
  @if(in_array($submission->status, ['draft', 'revision_requested'], true))
    <form method="POST" action="{{ route('dashboard.writing.revise', $submission) }}" class="mt-6 card p-6">@csrf
      <label class="block font-semibold text-navy-700" for="writing-revision">{{ $submission->status === 'draft' ? __('Continue your draft') : __('Revise your writing') }}</label>
      @if($submission->status === 'revision_requested')<p class="mt-2 text-sm text-navy-600">{{ __('Your teacher has requested a revision. Review the feedback below before resubmitting.') }}</p>@endif
      <textarea id="writing-revision" name="content" rows="14" required maxlength="20000" class="mt-3 w-full rounded-xl border border-navy-200 p-4">{{ $submission->content }}</textarea>
      <div class="mt-4 flex justify-end gap-3"><button name="status" value="draft" class="btn-outline">{{ __('Save draft') }}</button><button name="status" value="submitted" class="btn-primary">{{ __('Submit') }}</button></div>
    </form>
  @else
    <div class="mt-6 card p-6"><h2 class="font-bold text-navy-900">{{ __('Your submitted writing') }}</h2><div class="mt-3 whitespace-pre-wrap leading-7 text-navy-800">{{ $submission->content }}</div></div>
  @endif

  <section class="mt-10"><h2 class="text-xl font-bold text-navy-900">{{ __('Teacher feedback') }}</h2>
    @forelse($submission->reviews as $review)
      <article class="mt-4 card p-6"><div class="text-sm text-navy-500">{{ __('Feedback from') }} {{ $review->teacher->name }} · {{ $review->published_at->translatedFormat('j M Y') }}</div><p class="mt-3 whitespace-pre-wrap text-navy-800">{{ $review->overall_feedback }}</p>
        <p class="mt-3 text-xs text-navy-500">{{ __('Scores are interpreted relative to the assigned task and your CEFR level: 1 = limited evidence, 2 = partial or inconsistent, 3 = meets task expectations, 4 = controlled and effective.') }}</p><div class="mt-4 grid gap-2 sm:grid-cols-2">@foreach($review->rubric_scores as $criterion => $score)<div class="flex justify-between rounded-lg bg-navy-50 px-3 py-2 text-sm"><span>{{ __(\App\Services\Learning\WritingSubmissionService::RUBRIC[$criterion] ?? $criterion) }}</span><strong>{{ $score }}/4</strong></div>@endforeach</div>
        @if($review->strengths)<div class="mt-4"><h3 class="font-semibold">{{ __('What went well') }}</h3><p class="mt-1 whitespace-pre-wrap text-navy-700">{{ $review->strengths }}</p></div>@endif
        @if($review->improvements)<div class="mt-4"><h3 class="font-semibold">{{ __('What to improve') }}</h3><p class="mt-1 whitespace-pre-wrap text-navy-700">{{ $review->improvements }}</p></div>@endif
        @if($review->next_steps)<div class="mt-4"><h3 class="font-semibold">{{ __('Next steps') }}</h3><p class="mt-1 whitespace-pre-wrap text-navy-700">{{ $review->next_steps }}</p></div>@endif
      </article>
    @empty<p class="mt-3 text-navy-600">{{ __('Your teacher has not published feedback yet.') }}</p>@endforelse
  </section>

  <section class="mt-10"><h2 class="text-xl font-bold text-navy-900">{{ __('Version history') }}</h2><div class="mt-4 space-y-3">
    @foreach($submission->versions->reverse() as $version)
      <details class="card p-4"><summary class="cursor-pointer font-semibold">{{ __('Version') }} {{ $version->version_number }} · {{ $version->word_count }} {{ __('words') }} · {{ $version->created_at->translatedFormat('j M Y, H:i') }}</summary><div class="mt-3 whitespace-pre-wrap text-navy-700">{{ $version->content }}</div></details>
    @endforeach
  </div></section>
</section>
</x-layout>
