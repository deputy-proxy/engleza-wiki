<div class="mt-8">
@if(count($questions) === 0)
  <div class="card p-6 text-navy-600" data-testid="no-questions">{{ __('No questions available for this exercise.') }}</div>
@else
  @if(!$submitted)
    <div class="space-y-6" data-testid="exercise-questions">
      @foreach($questions as $i => $q)
        <div class="card p-6">
          <div class="text-sm text-brand-600 font-semibold">{{ __('Question') }} {{ $i + 1 }}</div>
          <h3 class="mt-2 text-lg font-bold text-navy-900" data-testid="ex-question-{{ $q['id'] }}">{{ $q['text'] }}</h3>
          @if(in_array($q['type'], ['single_choice', 'true_false'], true))
            <div class="mt-4 space-y-2">
              @foreach($q['options'] as $opt)
                @php $selected = (int) ($answers[$q['id']] ?? 0) === (int) $opt['id']; @endphp
                <button type="button" wire:click="pick({{ $q['id'] }}, {{ $opt['id'] }})" aria-pressed="{{ $selected ? 'true' : 'false' }}"
                  class="w-full text-left flex items-center gap-3 rounded-xl border px-4 py-3 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 {{ $selected ? 'border-brand-500 bg-brand-50' : 'border-navy-200 hover:border-brand-300 bg-white' }}"
                  data-testid="ex-option-{{ $opt['id'] }}">
                  <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold {{ $selected ? 'bg-brand-500 text-white' : 'bg-navy-100 text-navy-700' }}">{{ $opt['label'] }}</span>
                  <span class="text-navy-900">{{ $opt['value'] }}</span>
                </button>
              @endforeach
            </div>
          @elseif($q['type'] === 'multiple_choice')
            <div class="mt-4 space-y-2">
              @foreach($q['options'] as $opt)
                @php $selected = in_array((int) $opt['id'], array_map('intval', (array) ($answers[$q['id']] ?? [])), true); @endphp
                <button type="button" wire:click="toggleOption({{ $q['id'] }}, {{ $opt['id'] }})" aria-pressed="{{ $selected ? 'true' : 'false' }}"
                  class="w-full text-left flex items-center gap-3 rounded-xl border px-4 py-3 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 {{ $selected ? 'border-brand-500 bg-brand-50' : 'border-navy-200 hover:border-brand-300 bg-white' }}">
                  <span class="inline-flex h-6 w-6 items-center justify-center rounded-md border {{ $selected ? 'border-brand-500 bg-brand-500 text-white' : 'border-navy-300' }}">{{ $selected ? '✓' : '' }}</span>
                  <span class="text-navy-900">{{ $opt['value'] }}</span>
                </button>
              @endforeach
            </div>
          @elseif(in_array($q['type'], ['text', 'short_text'], true))
            <label class="mt-4 block text-sm font-medium text-navy-700">
              {{ __('Your answer') }}
              <input type="text" maxlength="2000" value="{{ $answers[$q['id']] ?? '' }}"
                wire:input.debounce.300ms="setAnswer({{ $q['id'] }}, $event.target.value)"
                class="mt-2 w-full rounded-xl border border-navy-200 px-4 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            </label>
          @elseif($q['type'] === 'matching')
            <div class="mt-4 space-y-3">
              @foreach($q['configuration']['pairs'] ?? [] as $pair)
                <label class="grid gap-2 sm:grid-cols-2 sm:items-center text-sm text-navy-700">
                  <span>{{ $pair['left'] }}</span>
                  <select wire:change="setStructuredAnswer({{ $q['id'] }}, @js($pair['left']), $event.target.value)"
                    class="w-full rounded-xl border border-navy-200 px-3 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    <option value="">{{ __('Choose a match') }}</option>
                    @foreach($pair['choices'] as $choice)
                      <option value="{{ $choice }}" @selected(($answers[$q['id']][$pair['left']] ?? '') === $choice)>{{ $choice }}</option>
                    @endforeach
                  </select>
                </label>
              @endforeach
            </div>
          @elseif($q['type'] === 'ordering')
            <div class="mt-4 space-y-3">
              @foreach($q['configuration']['items'] ?? [] as $i => $item)
                <label class="grid gap-2 sm:grid-cols-[5rem_1fr] sm:items-center text-sm text-navy-700">
                  <span>{{ __('Position') }} {{ $i + 1 }}</span>
                  <select wire:change="setStructuredAnswer({{ $q['id'] }}, @js((string) $i), $event.target.value)"
                    class="w-full rounded-xl border border-navy-200 px-3 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    <option value="">{{ __('Choose an item') }}</option>
                    @foreach($q['configuration']['items'] as $choice)
                      <option value="{{ $choice }}" @selected(($answers[$q['id']][$i] ?? '') === $choice)>{{ $choice }}</option>
                    @endforeach
                  </select>
                </label>
              @endforeach
            </div>
          @elseif($q['type'] === 'gap_fill')
            <div class="mt-4 space-y-3">
              @foreach($q['configuration']['gaps'] ?? [] as $key => $blank)
                <label class="block text-sm font-medium text-navy-700">
                  {{ str_replace('_', ' ', ucfirst($key)) }}
                  <input type="text" value="{{ $answers[$q['id']][$key] ?? '' }}"
                    wire:input.debounce.300ms="setStructuredAnswer({{ $q['id'] }}, @js($key), $event.target.value)"
                    class="mt-2 w-full rounded-xl border border-navy-200 px-4 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                </label>
              @endforeach
            </div>
          @endif
        </div>
      @endforeach
    </div>
    <div class="mt-6 flex justify-end">
      <button wire:click="submit" wire:loading.attr="disabled" class="btn-primary bg-emerald-600 hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" data-testid="exercise-submit">{{ __('Submit answers') }}</button>
    </div>
  @else
    <div class="card p-8 text-center bg-emerald-50 border-emerald-200" data-testid="exercise-result" role="status" aria-live="polite">
      <div class="text-sm uppercase font-semibold text-emerald-700 tracking-widest">{{ __('Your score') }}</div>
      <div class="mt-2 text-5xl font-extrabold text-navy-900" data-testid="exercise-score">{{ (float) ($attempt?->score ?? 0) }} / {{ (float) ($attempt?->total_points ?? 0) }}</div>
      <div class="mt-2 text-lg text-navy-700" data-testid="exercise-percentage">{{ $attempt?->percentage ?? 0 }}%</div>
    </div>
    <div class="mt-8 space-y-4">
      @foreach($questions as $i => $q)
        @php $ans = $attempt?->answers->firstWhere('question_id', $q['id']); $correctOptions = collect($q['options'])->where('is_correct', true); @endphp
        <div class="card p-6">
          <div class="text-sm text-brand-600 font-semibold flex items-center justify-between">
            <span>{{ __('Question') }} {{ $i + 1 }}</span>
            @if(!$ans)
              <span class="chip bg-navy-50 text-navy-600">{{ __('Not answered') }}</span>
            @elseif(!$showCorrectAnswers)
              <span class="chip bg-navy-50 text-navy-600">{{ __('Submitted') }}</span>
            @elseif($ans->is_correct)
              <span class="chip bg-emerald-50 text-emerald-700">{{ __('Correct') }}</span>
            @else
              <span class="chip bg-rose-50 text-rose-700">{{ __('Incorrect') }}</span>
            @endif
          </div>
          <h3 class="mt-2 font-bold text-navy-900">{{ $q['text'] }}</h3>
          @if($correctOptions->isNotEmpty())
            <div class="mt-3 text-sm text-navy-700">{{ __('Correct answer: ') }}<strong class="text-emerald-700">{{ $correctOptions->pluck('value')->join(', ') }}</strong></div>
          @elseif(!empty($q['answer_key'] ?? []))
            <div class="mt-3 text-sm font-semibold text-emerald-700">{{ __('Answer key') }}</div>
            <pre class="mt-1 overflow-x-auto rounded-lg bg-emerald-50 p-3 text-xs text-emerald-800">{{ json_encode($q['answer_key'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
          @endif
          @if(!empty(($q['explanation'] ?? null)))
            <div class="mt-2 text-sm text-navy-600"><em>{{ __('Explanation:') }}</em> {{ $q['explanation'] }}</div>
          @endif
        </div>
      @endforeach
    </div>
  @endif
@endif
</div>
