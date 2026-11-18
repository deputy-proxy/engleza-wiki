@php
$badges = [
  'card'=>['bg-yellow-50 text-yellow-700',__('Cards')],
  'listening'=>['bg-purple-50 text-purple-700',__('Listening')],
  'reading'=>['bg-rose-50 text-rose-700',__('Reading')],
  'writing'=>['bg-sky-50 text-sky-700',__('Writing')],
];
@endphp
<div x-data="{ hasFocus: false }" @click.outside="hasFocus=false; $wire.close()" class="relative max-w-xl">
  <form action="{{ route('search') }}" method="GET" class="flex gap-2 rounded-full bg-white p-2 shadow-[0_4px_30px_-8px_rgba(15,30,92,0.2)] ring-1 ring-navy-100" data-testid="live-search-form">
    <label class="flex-1 flex items-center gap-3 px-4">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-navy-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input name="q" wire:model.live.debounce.250ms="q" @focus="hasFocus=true; $wire.focus()"
             placeholder="{{ __('Search lessons, grammar, vocabulary...') }}"
             autocomplete="off" spellcheck="false"
             class="w-full border-0 bg-transparent py-3 text-sm text-navy-900 focus:outline-none"
             data-testid="live-search-input">
    </label>
    <button class="btn-primary" data-testid="live-search-submit">{{ __('Search') }}</button>
  </form>

  <div x-show="hasFocus && $wire.open && ($wire.resources.length > 0 || $wire.tests.length > 0)" x-cloak
       x-transition
       class="absolute z-30 mt-2 w-full rounded-2xl bg-white ring-1 ring-navy-100 shadow-[0_20px_50px_-15px_rgba(15,30,92,0.25)] overflow-hidden"
       data-testid="live-search-panel">
    @if(count($tests))
      <div class="border-b border-navy-50">
        <div class="px-5 pt-4 pb-2 text-xs font-semibold uppercase tracking-widest text-brand-600">{{ __('Tests') }}</div>
        @foreach($tests as $t)
          <a href="{{ route('tests.show', $t['slug']) }}" class="flex items-center justify-between px-5 py-3 hover:bg-brand-50 transition" data-testid="live-search-test-{{ $t['slug'] }}">
            <span class="text-sm font-medium text-navy-900">{{ $t['title'] }}</span>
            @if($t['level'])<span class="chip">{{ $t['level'] }}</span>@endif
          </a>
        @endforeach
      </div>
    @endif
    @if(count($resources))
      <div>
        <div class="px-5 pt-4 pb-2 text-xs font-semibold uppercase tracking-widest text-brand-600">{{ __('Resources') }}</div>
        @foreach($resources as $r)
          @php $b = $badges[$r['type']] ?? ['bg-navy-50 text-navy-700', __($r['type'])]; @endphp
          <a href="{{ route('resources.show', $r['slug']) }}" class="flex items-center justify-between px-5 py-3 hover:bg-brand-50 transition" data-testid="live-search-resource-{{ $r['slug'] }}">
            <span class="text-sm font-medium text-navy-900 line-clamp-1">{{ $r['title'] }}</span>
            <span class="flex items-center gap-2 flex-shrink-0">
              @if($r['level'])<span class="chip">{{ $r['level'] }}</span>@endif
              <span class="chip {{ $b[0] }}">{{ $b[1] }}</span>
            </span>
          </a>
        @endforeach
      </div>
    @endif
    <div class="border-t border-navy-50 bg-navy-50/40 px-5 py-3 text-xs text-navy-500">{{ __('Press Enter for full search results') }}</div>
  </div>
</div>
