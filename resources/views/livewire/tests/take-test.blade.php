<div x-data="{ endsAt: {{ $test->duration_minutes ? (($attempt->started_at?->timestamp ?? time())+$test->duration_minutes*60)*1000 : 0 }}, now: Date.now(), get remaining(){ if(!this.endsAt) return null; let s=Math.max(0,Math.floor((this.endsAt-this.now)/1000)); return `${Math.floor(s/60)}:${String(s%60).padStart(2,'0')}` } }"
     x-init="setInterval(()=>{ now=Date.now(); if(endsAt && now>=endsAt){ $wire.submit() } },1000)">
@php $q = $questions[$currentIndex] ?? null; $total = count($questions); $answered = count($answers); @endphp

<div class="flex items-center justify-between text-sm text-navy-600 mb-4" data-testid="test-header">
  <div>Question <span class="font-bold text-navy-900">{{ $currentIndex+1 }}</span> of {{ $total }} <span class="ml-2 text-navy-500">({{ $q['section'] ?? '' }})</span></div>
  <div x-show="endsAt" x-text="'⏱ ' + remaining" class="font-mono font-bold text-brand-600" data-testid="test-timer"></div>
</div>
<div class="h-2 w-full rounded-full bg-navy-100 overflow-hidden">
  <div class="h-full bg-brand-500 transition-all" style="width: {{ $total? round((($currentIndex+1)/$total)*100) : 0 }}%"></div>
</div>

@if($q)
<div class="mt-8 card p-8" data-testid="question-card">
  <h2 class="text-xl md:text-2xl font-bold text-navy-900" data-testid="question-text">{{ $q['text'] }}</h2>
  <div class="mt-6 space-y-3">
    @foreach($q['options'] as $opt)
      @php $selected = ($answers[$q['id']] ?? null) == $opt['id']; @endphp
      <button type="button" wire:click="selectOption({{ $q['id'] }}, {{ $opt['id'] }})"
        class="w-full text-left flex items-center gap-4 rounded-2xl border px-5 py-4 transition {{ $selected ? 'border-brand-500 bg-brand-50' : 'border-navy-200 hover:border-brand-300 bg-white' }}"
        data-testid="option-{{ $opt['id'] }}">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $selected ? 'bg-brand-500 text-white' : 'bg-navy-100 text-navy-700' }}">{{ $opt['label'] }}</span>
        <span class="text-navy-900">{{ $opt['value'] }}</span>
      </button>
    @endforeach
  </div>
</div>
@endif

<div class="mt-6 flex items-center justify-between">
  <button wire:click="prev" @disabled($currentIndex===0) class="btn-outline disabled:opacity-40" data-testid="prev-btn">← Previous</button>
  <div class="text-sm text-navy-500" data-testid="answered-counter">{{ $answered }} / {{ $total }} answered</div>
  @if($currentIndex < $total-1)
    <button wire:click="next" class="btn-primary" data-testid="next-btn">Next →</button>
  @else
    <button wire:click="submit" wire:loading.attr="disabled" class="btn-primary bg-emerald-600 hover:bg-emerald-700" data-testid="submit-btn">Submit test</button>
  @endif
</div>

<div class="mt-8 flex flex-wrap gap-2" data-testid="question-nav">
  @foreach($questions as $i=>$qq)
    <button wire:click="jumpTo({{ $i }})" class="h-9 w-9 rounded-full text-sm font-semibold {{ $i===$currentIndex ? 'bg-brand-500 text-white' : (isset($answers[$qq['id']]) ? 'bg-emerald-100 text-emerald-800' : 'bg-navy-100 text-navy-700') }}">{{ $i+1 }}</button>
  @endforeach
</div>
</div>
