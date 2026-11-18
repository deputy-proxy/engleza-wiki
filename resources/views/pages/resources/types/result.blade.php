<x-layout :robots-index="false" :resource="$resource" :title="__('Your result – ').$resource->translated('title')">
<section class="mx-auto max-w-3xl px-6 py-12">
  <div class="card p-8 text-center" data-testid="learning-result">
    <div class="text-sm font-semibold uppercase text-brand-600 tracking-widest">{{ __('Exercise completed') }}</div>
    <div class="mt-3 text-5xl font-extrabold text-navy-900" data-testid="learning-result-score">
      @if($attempt->total_points !== null)
        {{ \Illuminate\Support\Number::format((float) $attempt->score, 2) }} / {{ \Illuminate\Support\Number::format((float) $attempt->total_points, 2) }}
      @else
        {{ \Illuminate\Support\Number::format((float) $attempt->score, 2) }} {{ __('points') }}
      @endif
    </div>
    <div class="mt-2 text-xl text-navy-600" data-testid="learning-result-percentage">{{ \Illuminate\Support\Number::format((float) $attempt->percentage, 1) }}%</div>
  </div>

  @if(!empty($attempt->breakdown['topics']))
    <h2 class="mt-10 text-2xl font-bold text-navy-900">{{ __('Topic breakdown') }}</h2>
    <div class="mt-4 space-y-3" data-testid="learning-topic-breakdown">
      @foreach($attempt->breakdown['topics'] as $topic)
        <div class="card p-4">
          <div class="flex justify-between text-sm mb-1">
            <span class="font-semibold text-navy-900">{{ $topic['name'] }}</span>
            <span class="text-navy-600">{{ \Illuminate\Support\Number::format((float) $topic['percentage'], 1) }}% · {{ \Illuminate\Support\Number::format((float) $topic['earned_points'], 2) }} / {{ \Illuminate\Support\Number::format((float) $topic['total_points'], 2) }} {{ __('points') }}</span>
          </div>
          <div class="h-2.5 w-full rounded-full bg-navy-100 overflow-hidden"><div class="h-full bg-brand-500" style="width: {{ $topic['percentage'] }}%"></div></div>
        </div>
      @endforeach
    </div>
  @endif

  @if(!empty($questions))
    <h2 class="mt-10 text-2xl font-bold text-navy-900">{{ __('Answer review') }}</h2>
    <div class="mt-4 space-y-4">
    @foreach($questions as $question)
      @php
        $answer = $attempt->answers->firstWhere('question_id', $question['id']);
        $selectedOption = collect($question['options'])->firstWhere('id', $answer?->question_option_id);
        $correctOption = collect($question['options'])->firstWhere('is_correct', true);
      @endphp
      <div class="card p-5">
        <div class="flex items-center justify-between gap-4">
          <h3 class="font-semibold text-navy-900">{{ $question['text'] }}</h3>
          @if(!$answer)
            <span class="chip bg-navy-50 text-navy-600">{{ __('Not answered') }}</span>
          @elseif(!$showCorrectAnswers)
            <span class="chip bg-navy-50 text-navy-600">{{ __('Submitted') }}</span>
          @elseif($answer->is_correct)
            <span class="chip bg-emerald-50 text-emerald-700">{{ __('Correct') }}</span>
          @else
            <span class="chip bg-rose-50 text-rose-700">{{ __('Incorrect') }}</span>
          @endif
        </div>
        @if($selectedOption)
          <p class="mt-2 text-sm text-navy-600">{{ __('Your answer:') }} {{ $selectedOption['label'] }}. {{ $selectedOption['value'] }}</p>
        @else
          <p class="mt-2 text-sm text-navy-500">{{ __('Not answered') }}</p>
        @endif
        @if($showCorrectAnswers && $correctOption)
          <p class="mt-2 text-sm text-emerald-700">{{ __('Correct answer: ') }}{{ $correctOption['label'] }}. {{ $correctOption['value'] }}</p>
        @endif
        @if($showCorrectAnswers && ($question['explanation'] ?? null))
          <p class="mt-2 text-sm text-navy-600">{{ ($question['explanation'] ?? null) }}</p>
        @endif
      </div>
    @endforeach
    </div>
  @endif
  <a href="{{ route('resources.show', $resource->slug) }}" class="mt-8 inline-flex btn-outline">{{ __('Back to resource') }}</a>
</section>
</x-layout>
