<x-layout :robots-index="false" :title="__('Review writing — ').$group->name">
<section class="mx-auto max-w-4xl px-6 py-12">
  <a href="{{ route('classes.writing.index', $group) }}" class="text-sm font-semibold text-brand-700">← {{ __('All writing submissions') }}</a>
  <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
    <div><div class="text-sm text-navy-500">{{ $group->name }} · {{ $submission->user?->name }}</div><h1 class="mt-1 text-3xl font-extrabold text-navy-900">{{ $submission->writingResource->resource->translated('title') }}</h1></div>
    <span class="chip">{{ __(str_replace('_', ' ', ucfirst($submission->status))) }}</span>
  </div>
  <div class="mt-6 card p-6">
    <h2 class="font-bold text-navy-900">{{ __('Current submission') }}</h2>
    <div class="mt-4 whitespace-pre-wrap leading-7 text-navy-800">{{ $submission->content }}</div>
    <p class="mt-4 text-sm text-navy-500">{{ $submission->word_count }} {{ __('words') }} · {{ __('Version') }} {{ $submission->versions->last()?->version_number ?? 1 }}</p>
  </div>

  @if(in_array($submission->status, ['submitted', 'resubmitted'], true))
    <form method="POST" action="{{ route('classes.writing.begin-review', [$group, $submission]) }}" class="mt-6">@csrf<button class="btn-primary">{{ __('Start review') }}</button></form>
  @elseif($submission->status === 'under_review')
    <form method="POST" action="{{ route('classes.writing.feedback', [$group, $submission]) }}" class="mt-6 card space-y-5 p-6">
      @csrf
      <div><h2 class="text-xl font-bold text-navy-900">{{ __('Teacher feedback') }}</h2><p class="mt-1 text-sm text-navy-600">{{ __('Score each criterion against the task and the learner’s CEFR level. Scores are teacher judgements, not automated results.') }}</p></div>
      <p class="text-xs text-navy-500">{{ __('1 = limited evidence; 2 = partial or inconsistent; 3 = meets task expectations; 4 = controlled and effective. Interpret each score relative to this task and level.') }}</p>
      <div class="grid gap-4 sm:grid-cols-2">
        @foreach(\App\Services\Learning\WritingSubmissionService::RUBRIC as $key => $label)
          <label class="block text-sm font-semibold text-navy-700">{{ __($label) }}
            <select name="rubric_scores[{{ $key }}]" required class="mt-1 w-full rounded-lg border border-navy-200 p-2 text-navy-900">
              <option value="">{{ __('Choose a score') }}</option>
              <option value="1">1 · {{ __('Beginning') }}</option><option value="2">2 · {{ __('Developing') }}</option><option value="3">3 · {{ __('Effective') }}</option><option value="4">4 · {{ __('Strong') }}</option>
            </select>
          </label>
        @endforeach
      </div>
      <label class="block text-sm font-semibold text-navy-700">{{ __('Overall feedback') }}<textarea name="overall_feedback" required rows="4" maxlength="5000" class="mt-1 w-full rounded-lg border border-navy-200 p-3"></textarea></label>
      <label class="block text-sm font-semibold text-navy-700">{{ __('What went well') }}<textarea name="strengths" rows="3" maxlength="3000" class="mt-1 w-full rounded-lg border border-navy-200 p-3"></textarea></label>
      <label class="block text-sm font-semibold text-navy-700">{{ __('What to improve') }}<textarea name="improvements" rows="3" maxlength="3000" class="mt-1 w-full rounded-lg border border-navy-200 p-3"></textarea></label>
      <label class="block text-sm font-semibold text-navy-700">{{ __('Next steps') }}<textarea name="next_steps" rows="3" maxlength="3000" class="mt-1 w-full rounded-lg border border-navy-200 p-3"></textarea></label>
      <button class="btn-primary">{{ __('Publish feedback') }}</button>
    </form>
  @elseif($submission->status === 'feedback_published')
    <div class="mt-5 flex flex-wrap gap-3">
      <form method="POST" action="{{ route('classes.writing.request-revision', [$group, $submission]) }}">@csrf<button class="btn-outline">{{ __('Request revision') }}</button></form>
      <form method="POST" action="{{ route('classes.writing.complete', [$group, $submission]) }}">@csrf<button class="btn-primary">{{ __('Mark complete') }}</button></form>
    </div>
  @endif

  @if($submission->reviews->isNotEmpty())
    <section class="mt-8 card p-6"><h2 class="font-bold text-navy-900">{{ __('Published feedback history') }}</h2>
      @foreach($submission->reviews as $review)
        <div class="mt-4 border-t border-navy-100 pt-4"><p class="text-sm text-navy-500">{{ __('Reviewed by') }} {{ $review->teacher->name }} · {{ $review->published_at->translatedFormat('j M Y') }} · {{ __('Version') }} {{ $review->version->version_number }}</p><p class="mt-2 whitespace-pre-wrap">{{ $review->overall_feedback }}</p><div class="mt-3 flex flex-wrap gap-2">@foreach($review->rubric_scores as $criterion => $score)<span class="chip">{{ __(\App\Services\Learning\WritingSubmissionService::RUBRIC[$criterion] ?? $criterion) }}: {{ $score }}/4</span>@endforeach</div>
          @if($review->strengths)<p class="mt-3 text-sm"><strong>{{ __('What went well') }}:</strong> {{ $review->strengths }}</p>@endif
          @if($review->improvements)<p class="mt-2 text-sm"><strong>{{ __('What to improve') }}:</strong> {{ $review->improvements }}</p>@endif
          @if($review->next_steps)<p class="mt-2 text-sm"><strong>{{ __('Next steps') }}:</strong> {{ $review->next_steps }}</p>@endif
        </div>
      @endforeach
    </section>
  @endif

  <section class="mt-10"><h2 class="text-xl font-bold text-navy-900">{{ __('Version history') }}</h2><div class="mt-4 space-y-3">
    @foreach($submission->versions->reverse() as $version)
      <details class="card p-4"><summary class="cursor-pointer font-semibold">{{ __('Version') }} {{ $version->version_number }} · {{ $version->word_count }} {{ __('words') }} · {{ $version->created_at->translatedFormat('j M Y, H:i') }}</summary><div class="mt-3 whitespace-pre-wrap text-navy-700">{{ $version->content }}</div></details>
    @endforeach
  </div></section>
</section>
</x-layout>
