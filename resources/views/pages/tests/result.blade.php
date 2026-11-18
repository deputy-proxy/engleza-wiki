<x-layout :robots-index="false" :title="$test->translated('title').' · '.__('Result')" description="{{ __('Your assessment result and question review.') }}">
<div class="mx-auto max-w-4xl px-4 py-12">
  <a href="{{ route('tests.show', $test->slug) }}" class="text-sm font-semibold text-brand-600 hover:underline">{{ __('← Back to test') }}</a>
  <div class="mt-6 card p-8 text-center">
    <p class="text-sm font-semibold uppercase tracking-widest text-navy-500">{{ __('Assessment result') }}</p>
    <h1 class="mt-2 text-3xl font-extrabold text-navy-900">{{ $test->translated('title') }}</h1>
    <div class="mt-6 text-5xl font-extrabold text-navy-900">{{ \Illuminate\Support\Number::format((float) ($attempt->percentage ?? 0), 1) }}%</div>
    <p class="mt-2 text-navy-600">{{ \Illuminate\Support\Number::format((float) ($attempt->score ?? 0), 2) }} / {{ \Illuminate\Support\Number::format((float) ($attempt->total_points ?? 0), 2) }} {{ __('points') }}</p>
    @if($attempt->status === 'expired')
      <p class="mt-4 text-sm font-semibold text-amber-700">{{ __('Time expired. This attempt expired, and answers saved before the deadline were scored.') }}</p>
    @elseif($attempt->result?->passed)
      <p class="mt-4 text-sm font-semibold text-emerald-700">{{ __('You passed this assessment.') }}</p>
    @else
      <p class="mt-4 text-sm font-semibold text-navy-600">{{ __('Keep practising and try again.') }}</p>
    @endif
  </div>

  <section class="mt-10">
    <h2 class="text-xl font-bold text-navy-900">{{ __('Question review') }}</h2>
    @if($questions === [])
      <p class="mt-3 text-sm text-navy-600">{{ __('A question-by-question review is unavailable for this historical attempt.') }}</p>
    @else
      <div class="mt-5 space-y-4">
        @foreach($questions as $i => $question)
          @php
            $answer = $attempt->answers->firstWhere('question_id', $question['id']);
            $payload = $answer?->answer_payload ?? ($answer?->question_option_id !== null ? (int) $answer->question_option_id : $answer?->answer_text);
            $correctOptions = collect($question['options'])->where('is_correct', true);
            $selectedValues = [];
            if (is_array($payload) && array_is_list($payload) && $question['options'] !== []) {
                $selectedValues = collect($question['options'])->whereIn('id', array_map('intval', $payload))->pluck('value')->all();
            }
          @endphp
          <article class="card p-6">
            <div class="flex items-center justify-between gap-3">
              <p class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ __('Question') }} {{ $i + 1 }} · {{ $question['section'] ?? __('General') }}</p>
              @if(!$answer)
                <span class="chip bg-navy-50 text-navy-600">{{ __('Not answered') }}</span>
              @elseif(!array_key_exists('answer_key', $question))
                <span class="chip bg-navy-50 text-navy-600">{{ __('Submitted') }}</span>
              @elseif($answer->is_correct)
                <span class="chip bg-emerald-50 text-emerald-700">{{ __('Correct') }}</span>
              @else
                <span class="chip bg-rose-50 text-rose-700">{{ __('Incorrect') }}</span>
              @endif
            </div>
            <h3 class="mt-2 font-bold text-navy-900">{{ $question['text'] }}</h3>
            @if($answer)
              <p class="mt-3 text-sm text-navy-700"><strong>{{ __('Your answer:') }}</strong>
                @if($selectedValues !== [])
                  {{ implode(', ', $selectedValues) }}
                @elseif(is_array($payload))
                  <code>{{ json_encode($payload, JSON_UNESCAPED_UNICODE) }}</code>
                @elseif($payload !== null)
                  {{ $payload }}
                @else
                  {{ __('Not answered') }}
                @endif
              </p>
            @endif
            @if(array_key_exists('answer_key', $question))
              @if($correctOptions->isNotEmpty())
                <p class="mt-2 text-sm text-emerald-700"><strong>{{ __('Correct answer:') }}</strong> {{ $correctOptions->pluck('value')->join(', ') }}</p>
              @elseif(isset($question['answer_key']['accepted_answers']))
                <p class="mt-2 text-sm text-emerald-700"><strong>{{ __('Accepted answers:') }}</strong> {{ implode(', ', $question['answer_key']['accepted_answers']) }}</p>
              @else
                <p class="mt-2 text-sm font-semibold text-emerald-700">{{ __('Answer key') }}</p>
                <pre class="mt-1 overflow-x-auto rounded-lg bg-emerald-50 p-3 text-xs text-emerald-800">{{ json_encode($question['answer_key'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
              @endif
              @if(!empty($question['explanation']))
                <p class="mt-3 text-sm text-navy-600"><strong>{{ __('Explanation:') }}</strong> {{ $question['explanation'] }}</p>
              @endif
            @endif
          </article>
        @endforeach
      </div>
    @endif
  </section>

  @if($recommendations->isNotEmpty())
    <section class="mt-10">
      <h2 class="text-xl font-bold text-navy-900">{{ __('Recommended next steps') }}</h2>
      <div class="mt-4 grid gap-4 sm:grid-cols-2">
        @foreach($recommendations as $resource)
          <a href="{{ route('resources.show', $resource) }}" class="card p-5 hover:border-brand-300">
            <h3 class="font-bold text-navy-900">{{ $resource->translated('title') }}</h3>
            <p class="mt-2 text-sm text-navy-600">{{ $resource->translated('excerpt') }}</p>
          </a>
        @endforeach
      </div>
    </section>
  @endif
</div>
</x-layout>
