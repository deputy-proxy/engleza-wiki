<x-layout title="{{ __('Adaptive practice · engleza.wiki') }}" description="{{ __('Personalised practice based on your learning evidence.') }}">
<section class="mx-auto max-w-4xl px-6 py-12">
  <div class="flex flex-wrap items-start justify-between gap-4">
    <div>
      <p class="text-sm font-semibold uppercase tracking-wide text-brand-600">{{ __('Personalised practice') }}</p>
      <h1 class="mt-2 text-3xl font-extrabold text-navy-900">{{ __('Practice session') }}</h1>
      <p class="mt-2 text-navy-600">{{ __('Questions are selected from published learning content and adjusted to your current evidence.') }}</p>
    </div>
    <div class="card px-4 py-3 text-sm text-navy-600">
      {{ __('Difficulty range') }}: <strong>{{ __($session->difficulty_min) }}–{{ __($session->difficulty_max) }}</strong>
    </div>
  </div>

  @if($session->status === 'completed')
    <div class="card mt-6 p-6">
      <p class="text-sm text-navy-500">{{ __('Session complete') }}</p>
      <p class="mt-1 text-3xl font-extrabold text-navy-900">{{ number_format((float) $session->percentage, 1) }}%</p>
      <p class="mt-1 text-sm text-navy-600">{{ $session->earned_points }} {{ __('of') }} {{ $session->total_points }} {{ __('points') }}</p>
      @if($xpAwarded > 0)
        <p class="mt-2 text-sm font-semibold text-brand-700">{{ __('You earned :points XP for this session.', ['points' => $xpAwarded]) }}</p>
      @else
        <p class="mt-2 text-xs text-navy-500">{{ __('No XP was awarded for this session. Daily practice rewards are limited to three sessions.') }}</p>
      @endif
      <form method="POST" action="{{ route('practice.start') }}" class="mt-4">@csrf<button class="btn-primary" type="submit">{{ __('Start another session') }}</button></form>
    </div>
    <h2 class="mt-8 text-xl font-bold text-navy-900">{{ __('Answer review') }}</h2>
    <div class="mt-4 space-y-4">
      @foreach($questions as $index => $question)
        <article class="card p-5">
          <div class="flex items-center justify-between gap-3"><span class="text-sm font-semibold text-navy-500">{{ __('Question') }} {{ $index + 1 }}</span><strong class="text-sm {{ $question['is_correct'] ? 'text-emerald-700' : 'text-navy-600' }}">{{ $question['is_correct'] === null ? __('Not answered') : ($question['is_correct'] ? __('Correct') : __('Review this answer')) }}</strong></div>
          <h3 class="mt-2 font-semibold text-navy-900">{{ $question['text'] }}</h3>
          @if($question['type'] === 'single_choice' || $question['type'] === 'true_false' || $question['type'] === 'multiple_choice')
            <ul class="mt-3 space-y-1 text-sm">
              @foreach($question['options'] as $option)
                @if($option['is_correct'])<li class="font-semibold text-emerald-700">{{ __('Correct answer: ') }}{{ $option['label'] ?: $option['value'] }}</li>@endif
              @endforeach
            </ul>
          @elseif($question['answer_key'])
            <pre class="mt-3 overflow-x-auto rounded-lg bg-navy-50 p-3 text-xs text-navy-700">{{ json_encode($question['answer_key'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
          @endif
          @if($question['explanation'])<p class="mt-3 text-sm text-navy-600">{{ $question['explanation'] }}</p>@endif
        </article>
      @endforeach
    </div>
  @else
    <form method="POST" action="{{ route('practice.submit', $session) }}" class="mt-6 space-y-5">
      @csrf
      @foreach($questions as $index => $question)
        <article class="card p-6">
          <div class="flex items-center justify-between gap-3">
            <span class="text-sm font-semibold text-navy-500">{{ __('Question') }} {{ $index + 1 }} {{ __('of') }} {{ count($questions) }}</span>
            <span class="chip">{{ __($question['difficulty']) }}</span>
          </div>
          <h2 class="mt-3 text-lg font-bold text-navy-900">{{ $question['text'] }}</h2>

          @if(in_array($question['type'], ['single_choice', 'true_false'], true))
            <div class="mt-4 space-y-2">
              @foreach($question['options'] as $option)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-navy-200 p-3">
                  <input type="radio" name="answers[{{ $question['id'] }}]" value="{{ $option['id'] }}" @checked((string) $question['saved_answer'] === (string) $option['id'])>
                  <span>{{ $option['label'] ?: $option['value'] }}</span>
                </label>
              @endforeach
            </div>
          @elseif($question['type'] === 'multiple_choice')
            <div class="mt-4 space-y-2">
              @foreach($question['options'] as $option)
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-navy-200 p-3">
                  <input type="checkbox" name="answers[{{ $question['id'] }}][]" value="{{ $option['id'] }}" @checked(is_array($question['saved_answer']) && in_array($option['id'], $question['saved_answer']))>
                  <span>{{ $option['label'] ?: $option['value'] }}</span>
                </label>
              @endforeach
            </div>
          @elseif(in_array($question['type'], ['text', 'short_text'], true))
            <input class="mt-4 w-full rounded-lg border border-navy-200 p-3" type="text" name="answers[{{ $question['id'] }}]" value="{{ is_string($question['saved_answer']) ? $question['saved_answer'] : '' }}" maxlength="2000" placeholder="{{ __('Your answer') }}">
          @else
            <label class="mt-4 block text-sm font-medium text-navy-700">{{ __('Enter your response as JSON') }}</label>
            <textarea class="mt-2 w-full rounded-lg border border-navy-200 p-3 font-mono text-sm" name="answers[{{ $question['id'] }}]" rows="4" placeholder='{"key":"value"}'>{{ is_array($question['saved_answer']) ? json_encode($question['saved_answer'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea>
            <p class="mt-1 text-xs text-navy-500">{{ __("Use the exercise's configured keys and values.") }}</p>
          @endif
          @if($session->status === 'completed')
            <div class="mt-4 rounded-lg bg-navy-50 p-4">
              <p class="font-semibold">{{ $question['is_correct'] ? __('Correct') : __('Not quite') }}</p>
              @if($question['explanation'])<p class="mt-1 text-sm text-navy-700">{{ $question['explanation'] }}</p>@endif
            </div>
          @endif
          @error('answers.'.$question['id'])<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </article>
      @endforeach
      <button type="submit" class="btn-primary">{{ __('Submit practice session') }}</button>
    </form>
  @endif
</section>
</x-layout>
