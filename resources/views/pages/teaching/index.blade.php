<x-layout :robots-index="false" :title="__('Teaching dashboard').' — engleza.wiki'">
<section class="mx-auto max-w-6xl px-6 py-12">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div><p class="text-xs font-semibold uppercase tracking-widest text-brand-600">{{ __('Teacher workspace') }}</p><h1 class="mt-2 text-3xl font-extrabold text-navy-900">{{ __('Your classes') }}</h1><p class="mt-2 text-navy-600">{{ __('Manage class access, assignments and learner progress in one place.') }}</p></div>
    <a href="{{ route('teaching.create') }}" class="btn-primary">{{ __('Create a class') }}</a>
  </div>
  @if(session('status'))<p class="mt-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800" role="status">{{ __(session('status')) }}</p>@endif
  <div class="mt-8 grid gap-4 md:grid-cols-2">
    @if($groups->count() === 0)
      <div class="card p-8 md:col-span-2"><h2 class="text-lg font-bold text-navy-900">{{ __('No classes yet') }}</h2><p class="mt-2 text-navy-600">{{ __('Create a class to invite learners, assign published work and follow progress.') }}</p><a href="{{ route('teaching.create') }}" class="btn-primary mt-4 inline-flex">{{ __('Create a class') }}</a></div>
    @else
      @foreach($groups as $group)
        <article class="card p-6">
          <div class="flex items-start justify-between gap-3"><div><h2 class="text-xl font-bold text-navy-900">{{ $group->name }}</h2><p class="mt-1 text-sm text-navy-600">{{ __('Teacher') }}: {{ $group->teacher->name }}</p></div><span class="chip">{{ $group->is_active ? __('Active') : __('Archived') }}</span></div>
          <div class="mt-4 flex gap-5 text-sm text-navy-600"><span>{{ $group->members_count }} {{ __('learners') }}</span><span>{{ $group->attempts_count }} {{ __('attempts') }}</span></div>
          <p class="mt-3 text-xs text-navy-500">{{ __('Join code') }}: <span class="font-mono font-bold">{{ $group->join_code }}</span></p>
          <a class="btn-outline mt-5 inline-flex" href="{{ route('teaching.show', $group) }}">{{ __('Manage class') }}</a>
        </article>
      @endforeach
    @endif
  </div>
  <div class="mt-6">{{ $groups->links() }}</div>
</section>
</x-layout>
