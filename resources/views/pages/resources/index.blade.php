<x-layout :title="'Resources – engleza.wiki'">
<section class="mx-auto max-w-7xl px-6 py-12">
  <h1 class="text-4xl font-extrabold text-navy-900">All resources</h1>
  <p class="mt-2 text-navy-600">Articles, cards, listening, reading, writing, worksheets and lesson plans.</p>
  <form method="GET" class="mt-6 flex flex-wrap gap-3 items-end">
    <div><label class="block text-xs font-semibold text-navy-700 mb-1">Type</label>
      <select name="type" class="rounded-full border border-navy-200 px-4 py-2 text-sm" data-testid="filter-type">
        <option value="">All</option>@foreach($types as $k=>$v)<option value="{{ $k }}" @selected(request('type')===$k)>{{ $v }}</option>@endforeach
      </select></div>
    <div><label class="block text-xs font-semibold text-navy-700 mb-1">Level</label>
      <select name="level" class="rounded-full border border-navy-200 px-4 py-2 text-sm" data-testid="filter-level">
        <option value="">All</option>@foreach($levels as $l)<option value="{{ $l->slug }}" @selected(request('level')===$l->slug)>{{ $l->name }}</option>@endforeach
      </select></div>
    <div><label class="block text-xs font-semibold text-navy-700 mb-1">Skill</label>
      <select name="skill" class="rounded-full border border-navy-200 px-4 py-2 text-sm" data-testid="filter-skill">
        <option value="">All</option>@foreach($skills as $s)<option value="{{ $s->slug }}" @selected(request('skill')===$s->slug)>{{ $s->name }}</option>@endforeach
      </select></div>
    <button class="btn-primary" data-testid="filter-apply">Apply</button>
    <a href="{{ route('resources.index') }}" class="btn-ghost">Clear</a>
  </form>

  @php
    $badges = [
      'card'=>['bg-yellow-50 text-yellow-700','CARDS'],
      'listening'=>['bg-purple-50 text-purple-700','LISTENING'],
      'reading'=>['bg-rose-50 text-rose-700','READING'],
      'writing'=>['bg-sky-50 text-sky-700','WRITING'],
    ];
  @endphp
  <div class="mt-10 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($resources as $r)
      <a href="{{ route('resources.show',$r->slug) }}" class="card p-6 hover:shadow-lg transition" data-testid="resource-card-{{ $r->slug }}">
        <div class="flex items-center gap-2 text-xs">
          @if($r->level)<span class="chip">{{ $r->level->name }}</span>@endif
          @if(isset($badges[$r->type]))
            <span class="chip {{ $badges[$r->type][0] }} font-semibold">{{ $badges[$r->type][1] }}</span>
          @else
            <span class="chip bg-brand-50 text-brand-700">{{ ucfirst(str_replace('_',' ',$r->type)) }}</span>
          @endif
        </div>
        <h3 class="mt-4 text-lg font-bold text-navy-900">{{ $r->title }}</h3>
        <p class="mt-2 text-sm text-navy-600 line-clamp-3">{{ $r->excerpt }}</p>
      </a>
    @empty
      <div class="col-span-full text-center text-navy-600 py-12" data-testid="empty-resources">No resources found.</div>
    @endforelse
  </div>
  <div class="mt-8">{{ $resources->links() }}</div>
</section>
</x-layout>
