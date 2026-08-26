<div class="mt-8">
@if(count($questions)===0)
  <div class="card p-6 text-navy-600" data-testid="no-questions">No questions available for this exercise.</div>
@else
  @if(!$submitted)
    <div class="space-y-6" data-testid="exercise-questions">
      @foreach($questions as $i => $q)
        <div class="card p-6">
          <div class="text-sm text-brand-600 font-semibold">Question {{ $i+1 }}</div>
          <h3 class="mt-2 text-lg font-bold text-navy-900" data-testid="ex-question-{{ $q['id'] }}">{{ $q['text'] }}</h3>
          <div class="mt-4 space-y-2">
            @foreach($q['options'] as $opt)
              @php $selected = ($answers[$q['id']] ?? null) === $opt['id']; @endphp
              <button type="button" wire:click="pick({{ $q['id'] }}, {{ $opt['id'] }})"
                class="w-full text-left flex items-center gap-3 rounded-xl border px-4 py-3 transition {{ $selected ? 'border-brand-500 bg-brand-50' : 'border-navy-200 hover:border-brand-300 bg-white' }}"
                data-testid="ex-option-{{ $opt['id'] }}">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold {{ $selected ? 'bg-brand-500 text-white' : 'bg-navy-100 text-navy-700' }}">{{ $opt['label'] }}</span>
                <span class="text-navy-900">{{ $opt['value'] }}</span>
              </button>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
    <div class="mt-6 flex justify-end">
      <button wire:click="submit" wire:loading.attr="disabled" class="btn-primary bg-emerald-600 hover:bg-emerald-700" data-testid="exercise-submit">Submit answers</button>
    </div>
  @else
    <div class="card p-8 text-center bg-emerald-50 border-emerald-200" data-testid="exercise-result">
      <div class="text-sm uppercase font-semibold text-emerald-700 tracking-widest">Your score</div>
      <div class="mt-2 text-5xl font-extrabold text-navy-900" data-testid="exercise-score">{{ (int)($attempt?->score ?? 0) }} / {{ count($questions) }}</div>
      <div class="mt-2 text-lg text-navy-700" data-testid="exercise-percentage">{{ $attempt?->percentage ?? 0 }}%</div>
    </div>
    <div class="mt-8 space-y-4">
      @foreach($questions as $i => $q)
        @php $ans = $attempt?->answers->firstWhere('question_id', $q['id']); $correctOpt = collect($q['options'])->firstWhere('is_correct', true); @endphp
        <div class="card p-6">
          <div class="text-sm text-brand-600 font-semibold flex items-center justify-between">
            <span>Question {{ $i+1 }}</span>
            @if($ans?->is_correct)<span class="chip bg-emerald-50 text-emerald-700">Correct</span>
            @else<span class="chip bg-rose-50 text-rose-700">Incorrect</span>@endif
          </div>
          <h3 class="mt-2 font-bold text-navy-900">{{ $q['text'] }}</h3>
          <div class="mt-3 text-sm text-navy-700">Correct answer: <strong class="text-emerald-700">{{ $correctOpt['label'] ?? '' }}. {{ $correctOpt['value'] ?? '' }}</strong></div>
          @if(!empty($q['explanation']))
            <div class="mt-2 text-sm text-navy-600"><em>Explanation:</em> {{ $q['explanation'] }}</div>
          @endif
        </div>
      @endforeach
    </div>
  @endif
@endif
</div>
