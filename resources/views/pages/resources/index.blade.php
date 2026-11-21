<x-layout :robots-index="$robotsIndex" :title="__('Resources – engleza.wiki')" description="Explorează resurse gratuite de gramatică, vocabular, ascultare, citire, scriere și vorbire în limba engleză.">
<section class="mx-auto max-w-7xl px-6 py-12">
  <h1 class="text-4xl font-extrabold text-navy-900">{{ __('All resources') }}</h1>
  <p class="mt-2 text-navy-600">{{ __('Articles, cards, listening, reading, writing, speaking, worksheets and lesson plans.') }}</p>
  <form method="GET" class="mt-6 flex flex-wrap gap-3 items-end">
    <div><label for="resource-filter-type" class="block text-xs font-semibold text-navy-700 mb-1">{{ __('Type') }}</label>
      <select id="resource-filter-type" name="type" class="rounded-full border border-navy-200 px-4 py-2 text-sm" data-testid="filter-type">
        <option value="">{{ __('All') }}</option>@foreach($types as $k=>$v)<option value="{{ $k }}" @selected(request('type')===$k)>{{ $v }}</option>@endforeach
      </select></div>
    <div><label for="resource-filter-level" class="block text-xs font-semibold text-navy-700 mb-1">{{ __('Level') }}</label>
      <select id="resource-filter-level" name="level" class="rounded-full border border-navy-200 px-4 py-2 text-sm" data-testid="filter-level">
        <option value="">{{ __('All') }}</option>@foreach($levels as $l)<option value="{{ $l->slug }}" @selected(request('level')===$l->slug)>{{ $l->translated('name') }}</option>@endforeach
      </select></div>
    <div><label for="resource-filter-skill" class="block text-xs font-semibold text-navy-700 mb-1">{{ __('Skill') }}</label>
      <select id="resource-filter-skill" name="skill" class="rounded-full border border-navy-200 px-4 py-2 text-sm" data-testid="filter-skill">
        <option value="">{{ __('All') }}</option>@foreach($skills as $s)<option value="{{ $s->slug }}" @selected(request('skill')===$s->slug)>{{ $s->translated('name') }}</option>@endforeach
      </select></div>
    <button type="submit" class="btn-primary" data-testid="filter-apply">{{ __('Apply') }}</button>
    <a href="{{ route('resources.index') }}" class="btn-ghost">{{ __('Clear') }}</a>
  </form>

  @php
    $badges = [
      'card'=>['bg-yellow-50 text-yellow-700','CARDS'],
      'listening'=>['bg-purple-50 text-purple-700','LISTENING'],
      'reading'=>['bg-rose-50 text-rose-700','READING'],
      'writing'=>['bg-sky-50 text-sky-700','WRITING'],
      'speaking'=>['bg-emerald-50 text-emerald-700','SPEAKING'],
    ];
  @endphp
  <div class="mt-10 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($resources as $r)
      <a href="{{ route('resources.show',$r->slug) }}" class="card p-6 hover:shadow-lg transition" data-testid="resource-card-{{ $r->slug }}">
        <div class="flex items-center gap-2 text-xs">
          @if($r->level)<span class="chip">{{ $r->level->translated('name') }}</span>@endif
          @if(isset($badges[$r->type]))
            <span class="chip {{ $badges[$r->type][0] }} font-semibold">{{ __($badges[$r->type][1]) }}</span>
          @else
            <span class="chip bg-brand-50 text-brand-700">{{ __($r->type) }}</span>
          @endif
        </div>
        <h3 class="mt-4 text-lg font-bold text-navy-900">{{ $r->translated('title') }}</h3>
        <p class="mt-2 text-sm text-navy-600 line-clamp-3">{{ $r->translated('excerpt') }}</p>
      </a>
    @empty
      <div class="col-span-full text-center text-navy-600 py-12" data-testid="empty-resources">{{ __('No resources found.') }}</div>
    @endforelse
  </div>
  <div class="mt-8">{{ $resources->links() }}</div>
</section>
</x-layout>
