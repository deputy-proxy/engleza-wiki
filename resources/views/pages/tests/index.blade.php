<x-layout :title="__('Free English Tests – engleza.wiki')">
<section class="mx-auto max-w-7xl px-6 py-12">
  <h1 class="text-4xl md:text-5xl font-extrabold text-navy-900">{{ __('Free English Tests') }}</h1>
  <p class="mt-3 text-navy-600 max-w-2xl">{{ __('Take a free practice test for an indicative level estimate or practise a specific skill. Level tests sample grammar, vocabulary and reading only; they are not official CEFR examinations. No registration required.') }}</p>
  <div class="mt-10 grid md:grid-cols-2 lg:grid-cols-3 gap-6" data-testid="tests-grid">
    @foreach($tests as $t)
      <a href="{{ route('tests.show',$t->slug) }}" class="card p-6 hover:shadow-lg transition" data-testid="test-card-{{ $t->slug }}">
        <div class="flex gap-2">
          @if($t->level)<span class="chip">{{ $t->level->translated('name') }}</span>@endif
          @if($t->skill)<span class="chip bg-emerald-50 text-emerald-700">{{ $t->skill->translated('name') }}</span>@endif
          <span class="chip bg-yellow-50 text-yellow-700">{{ __(ucfirst($t->access_type)) }}</span>
        </div>
        <h3 class="mt-4 text-lg font-bold text-navy-900">{{ $t->translated('title') }}</h3>
        <p class="mt-2 text-sm text-navy-600">{{ $t->translated('description') }}</p>
        <div class="mt-4 text-sm text-navy-500">⏱ {{ $t->duration_minutes ?? '—' }} {{ __('min') }}</div>
      </a>
    @endforeach
  </div>
</section>
</x-layout>
