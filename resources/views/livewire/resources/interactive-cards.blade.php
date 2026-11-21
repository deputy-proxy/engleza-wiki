<div class="mt-6">
  @php $total = count($cards); @endphp
  @if($total === 0)
    <div class="card p-8 text-center text-navy-600" data-testid="cards-empty">{{ __('No cards yet.') }}</div>
  @else
    @php $card = $cards[$currentIndex]; @endphp
    <div class="flex items-center justify-between text-sm mb-3">
      <div class="text-navy-500" data-testid="card-counter">{{ __('Card ') }}<span class="font-bold text-navy-900">{{ $currentIndex+1 }}</span> {{ __('of') }} {{ $total }}</div>
      <div class="h-2 flex-1 mx-6 rounded-full bg-navy-100 overflow-hidden" role="progressbar" aria-label="{{ __('Flashcard progress') }}" aria-valuemin="1" aria-valuemax="{{ $total }}" aria-valuenow="{{ $currentIndex + 1 }}">
        <div class="h-full bg-brand-500 transition-all" style="width: {{ round((($currentIndex+1)/$total)*100) }}%"></div>
      </div>
    </div>

    <div class="perspective-[1200px] select-none" data-testid="flashcard">
      <button type="button" wire:click="reveal" aria-pressed="{{ $revealed ? 'true' : 'false' }}" class="relative flex min-h-[300px] w-full flex-col items-center justify-center rounded-3xl border border-navy-200 bg-white p-10 text-center shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-4" data-testid="flashcard-toggle">
        <span class="text-xs uppercase tracking-widest text-brand-700 font-semibold">{{ $revealed ? __('Answer') : __('Front') }}</span>
        <span class="mt-6 text-2xl font-bold text-navy-900" data-testid="{{ $revealed ? 'card-back' : 'card-front' }}">{!! nl2br(e($revealed ? $card['back'] : $card['front'])) !!}</span>
        <span class="mt-8 text-sm text-navy-700">{{ $revealed ? __('Click to flip back') : __('Click to reveal') }}</span>
      </button>
    </div>

    <div class="mt-6 flex items-center justify-between">
      <button type="button" wire:click="prev" @disabled($currentIndex===0) class="btn-outline disabled:bg-navy-50 disabled:text-navy-700 disabled:border-navy-200 disabled:cursor-not-allowed" data-testid="card-prev">{{ __('← Previous') }}</button>
      <button type="button" wire:click="reveal" class="btn-ghost text-brand-600" data-testid="card-reveal">{{ $revealed ? __('Hide answer') : __('Reveal answer') }}</button>
      <button type="button" wire:click="next" @disabled($currentIndex>=$total-1) class="btn-primary disabled:bg-navy-50 disabled:text-navy-700 disabled:border-navy-200 disabled:cursor-not-allowed" data-testid="card-next">{{ __('Next →') }}</button>
    </div>

    <div class="mt-8 flex flex-wrap gap-2">
      @foreach ($cards as $i => $c)
        <button type="button" wire:click="jump({{ $i }})" aria-label="{{ __('Go to card :number', ['number' => $i + 1]) }}" aria-current="{{ $i === $currentIndex ? 'step' : 'false' }}" class="h-8 w-8 rounded-full text-sm font-semibold {{ $i===$currentIndex ? 'bg-brand-500 text-white' : 'bg-navy-100 text-navy-700' }}">{{ $i+1 }}</button>
      @endforeach
    </div>
  @endif
</div>
