<div x-data="{ endsAt: {{ $test->duration_minutes ? (($attempt->started_at?->timestamp ?? time()) + $test->duration_minutes * 60) * 1000 : 0 }}, now: Date.now(), get remaining(){ if(!this.endsAt) return null; let s=Math.max(0,Math.floor((this.endsAt-this.now)/1000)); return Math.floor(s/60) + ':' + String(s%60).padStart(2,'0') } }"
     x-init="setInterval(()=>{ now=Date.now(); if(endsAt && now>=endsAt){ $wire.submit() } },1000)">
@php $q = $questions[$currentIndex] ?? null; $total = count($questions); $answered = count($answers); @endphp

<div class="flex items-center justify-between text-sm text-navy-600 mb-4" data-testid="test-header">
  <div>Question <span class="font-bold text-navy-900">{{ $currentIndex + 1 }}</span> of {{ $total }} <span class="ml-2 text-navy-500">({{ $q['section'] ?? '' }})</span></div>
  <div x-show="endsAt" aria-live="polite" x-text="'⏱ ' + remaining" class="font-mono font-bold text-brand-600" data-testid="test-timer"></div>
</div>
<div class="h-2 w-full rounded-full bg-navy-100 overflow-hidden">
  <div class="h-full bg-brand-500 transition-all" style="width: {{ $total ? round((($currentIndex + 1) / $total) * 100) : 0 }}%"></div>
</div>

@if($q)
<div class="mt-8 card p-8" data-testid="question-card">
  <h2 class="text-xl md:text-2xl font-bold text-navy-900" data-testid="question-text">{{ $q['text'] }}</h2>
  @if(in_array($q['type'], ['single_choice', 'true_false'], true))
    <div class="mt-6 space-y-3">
      @foreach($q['options'] as $opt)
        @php $selected = (int) ($answers[$q['id']] ?? 0) === (int) $opt['id']; @endphp
        <button type="button" wire:click="selectOption({{ $q['id'] }}, {{ $opt['id'] }})" aria-pressed="{{ $selected ? 'true' : 'false' }}"
          class="w-full text-left flex items-center gap-4 rounded-2xl border px-5 py-4 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 {{ $selected ? 'border-brand-500 bg-brand-50' : 'border-navy-200 hover:border-brand-300 bg-white' }}"
          data-testid="option-{{ $opt['id'] }}">
          <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $selected ? 'bg-brand-500 text-white' : 'bg-navy-100 text-navy-700' }}">{{ $opt['label'] }}</span>
          <span class="text-navy-900">{{ $opt['value'] }}</span>
        </button>
      @endforeach
    </div>
  @elseif($q['type'] === 'multiple_choice')
    <div class="mt-6 space-y-3">
      @foreach($q['options'] as $opt)
        @php $selected = in_array((int) $opt['id'], array_map('intval', (array) ($answers[$q['id']] ?? [])), true); @endphp
        <button type="button" wire:click="toggleOption({{ $q['id'] }}, {{ $opt['id'] }})" aria-pressed="{{ $selected ? 'true' : 'false' }}"
          class="w-full text-left flex items-center gap-4 rounded-2xl border px-5 py-4 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 {{ $selected ? 'border-brand-500 bg-brand-50' : 'border-navy-200 hover:border-brand-300 bg-white' }}">
          <span class="inline-flex h-7 w-7 items-center justify-center rounded-md border {{ $selected ? 'border-brand-500 bg-brand-500 text-white' : 'border-navy-300' }}">{{ $selected ? '✓' : '' }}</span>
          <span class="text-navy-900">{{ $opt['value'] }}</span>
        </button>
      @endforeach
    </div>
  @elseif(in_array($q['type'], ['text', 'short_text'], true))
    <label class="mt-6 block text-sm font-medium text-navy-700">Your answer
      <input type="text" maxlength="2000" value="{{ $answers[$q['id']] ?? '' }}" wire:input.debounce.300ms="setAnswer({{ $q['id'] }}, $event.target.value)"
        class="mt-2 w-full rounded-xl border border-navy-200 px-4 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
    </label>
  @elseif($q['type'] === 'matching')
    <div class="mt-6 space-y-3">
      @foreach($q['configuration']['pairs'] ?? [] as $pair)
        <label class="grid gap-2 sm:grid-cols-2 sm:items-center text-sm text-navy-700">
          <span>{{ $pair['left'] }}</span>
          <select wire:change="setStructuredAnswer({{ $q['id'] }}, @js($pair['left']), $event.target.value)" class="w-full rounded-xl border border-navy-200 px-3 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <option value="">Choose a match</option>
            @foreach($pair['choices'] as $choice)
              <option value="{{ $choice }}" @selected(($answers[$q['id']][$pair['left']] ?? '') === $choice)>{{ $choice }}</option>
            @endforeach
          </select>
        </label>
      @endforeach
    </div>
  @elseif($q['type'] === 'ordering')
    <div class="mt-6 space-y-3">
      @foreach($q['configuration']['items'] ?? [] as $i => $item)
        <label class="grid gap-2 sm:grid-cols-[5rem_1fr] sm:items-center text-sm text-navy-700">
          <span>Position {{ $i + 1 }}</span>
          <select wire:change="setStructuredAnswer({{ $q['id'] }}, @js((string) $i), $event.target.value)" class="w-full rounded-xl border border-navy-200 px-3 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
            <option value="">Choose an item</option>
            @foreach($q['configuration']['items'] as $choice)
              <option value="{{ $choice }}" @selected(($answers[$q['id']][$i] ?? '') === $choice)>{{ $choice }}</option>
            @endforeach
          </select>
        </label>
      @endforeach
    </div>
  @elseif($q['type'] === 'gap_fill')
    <div class="mt-6 space-y-3">
      @foreach($q['configuration']['gaps'] ?? [] as $key => $blank)
        <label class="block text-sm font-medium text-navy-700">{{ str_replace('_', ' ', ucfirst($key)) }}
          <input type="text" value="{{ $answers[$q['id']][$key] ?? '' }}" wire:input.debounce.300ms="setStructuredAnswer({{ $q['id'] }}, @js($key), $event.target.value)"
            class="mt-2 w-full rounded-xl border border-navy-200 px-4 py-3 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
        </label>
      @endforeach
    </div>
  @endif
</div>
@endif

<div class="mt-6 flex items-center justify-between">
  <button wire:click="prev" @disabled($currentIndex === 0) class="btn-outline disabled:opacity-40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" data-testid="prev-btn">← Previous</button>
  <div class="text-sm text-navy-500" data-testid="answered-counter">{{ $answered }} / {{ $total }} answered</div>
  @if($currentIndex < $total - 1)
    <button wire:click="next" class="btn-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" data-testid="next-btn">Next →</button>
  @else
    <button wire:click="submit" wire:loading.attr="disabled" class="btn-primary bg-emerald-600 hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" data-testid="submit-btn">Submit test</button>
  @endif
</div>

<div class="mt-8 flex flex-wrap gap-2" data-testid="question-nav" role="group" aria-label="Question navigation">
  @foreach($questions as $i => $qq)
    <button wire:click="jumpTo({{ $i }})" aria-current="{{ $i === $currentIndex ? 'step' : 'false' }}"
      class="h-9 w-9 rounded-full text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 {{ $i === $currentIndex ? 'bg-brand-500 text-white' : (isset($answers[$qq['id']]) ? 'bg-emerald-100 text-emerald-800' : 'bg-navy-100 text-navy-700') }}">{{ $i + 1 }}</button>
  @endforeach
</div>
</div>
