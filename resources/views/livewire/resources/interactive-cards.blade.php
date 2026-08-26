<div x-data="{ index: @entangle('currentIndex').live, revealed: @entangle('revealed').live }" class="mt-6">
  @php $total = count($cards); @endphp
  @if($total === 0)
    <div class="card p-8 text-center text-navy-600" data-testid="cards-empty">No cards yet.</div>
  @else
    @php $card = $cards[$currentIndex]; @endphp
    <div class="flex items-center justify-between text-sm mb-3">
      <div class="text-navy-500" data-testid="card-counter">Card <span class="font-bold text-navy-900">{{ $currentIndex+1 }}</span> of {{ $total }}</div>
      <div class="h-2 flex-1 mx-6 rounded-full bg-navy-100 overflow-hidden">
        <div class="h-full bg-brand-500 transition-all" style="width: {{ round((($currentIndex+1)/$total)*100) }}%"></div>
      </div>
    </div>

    <div class="perspective-[1200px] select-none" data-testid="flashcard">
      <div wire:click="reveal" tabindex="0" role="button" @keydown.enter.prevent="$wire.reveal()" @keydown.space.prevent="$wire.reveal()"
           class="relative min-h-[300px] cursor-pointer transition-transform duration-500"
           style="transform-style: preserve-3d; transform: {{ $revealed ? 'rotateY(180deg)' : 'rotateY(0deg)' }};">
        <div class="absolute inset-0 card p-10 flex flex-col items-center justify-center text-center" style="backface-visibility: hidden;">
          <div class="text-xs uppercase tracking-widest text-brand-600 font-semibold">Front</div>
          <div class="mt-6 text-2xl md:text-3xl font-bold text-navy-900" data-testid="card-front">{!! nl2br(e($card['front'])) !!}</div>
          <div class="mt-8 text-sm text-navy-500">Click to reveal</div>
        </div>
        <div class="absolute inset-0 card p-10 flex flex-col items-center justify-center text-center bg-brand-50" style="backface-visibility: hidden; transform: rotateY(180deg);">
          <div class="text-xs uppercase tracking-widest text-brand-700 font-semibold">Answer</div>
          <div class="mt-6 text-3xl md:text-4xl font-extrabold text-navy-900" data-testid="card-back">{!! nl2br(e($card['back'])) !!}</div>
          <div class="mt-8 text-sm text-brand-700">Click to flip back</div>
        </div>
      </div>
    </div>

    <div class="mt-6 flex items-center justify-between">
      <button wire:click="prev" @disabled($currentIndex===0) class="btn-outline disabled:opacity-40" data-testid="card-prev">← Previous</button>
      <button wire:click="reveal" class="btn-ghost text-brand-600" data-testid="card-reveal">{{ $revealed ? 'Hide answer' : 'Reveal answer' }}</button>
      <button wire:click="next" @disabled($currentIndex>=$total-1) class="btn-primary disabled:opacity-40" data-testid="card-next">Next →</button>
    </div>

    <div class="mt-8 flex flex-wrap gap-2">
      @foreach ($cards as $i => $c)
        <button wire:click="jump({{ $i }})" class="h-8 w-8 rounded-full text-sm font-semibold {{ $i===$currentIndex ? 'bg-brand-500 text-white' : 'bg-navy-100 text-navy-700' }}">{{ $i+1 }}</button>
      @endforeach
    </div>
  @endif
</div>
