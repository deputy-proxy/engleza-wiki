<x-layout :title="$resource->seo_title ?: $resource->title.' – engleza.wiki'" :description="$resource->seo_description ?: $resource->excerpt">
<article class="mx-auto max-w-4xl px-6 py-12">
  <nav class="text-sm text-navy-500 flex items-center gap-2" data-testid="breadcrumb">
    <a href="{{ route('home') }}" class="hover:text-brand-600">Home</a><span>/</span>
    <a href="{{ route('resources.index') }}" class="hover:text-brand-600">Resources</a><span>/</span>
    <span class="text-navy-900">{{ $resource->title }}</span>
  </nav>
  <div class="mt-6 flex flex-wrap gap-2">
    <span class="chip bg-sky-50 text-sky-700 font-semibold">WRITING</span>
    @if($resource->level)<span class="chip">{{ $resource->level->name }}</span>@endif
  </div>
  <h1 class="mt-4 text-4xl md:text-5xl font-extrabold text-navy-900" data-testid="resource-title">{{ $resource->title }}</h1>
  <p class="mt-3 text-lg text-navy-600">{{ $resource->excerpt }}</p>

  @php $wr = $resource->writingResource; @endphp
  <div class="mt-8 card p-8 bg-sky-50/60 border-sky-100" data-testid="writing-task">
    <div class="text-xs uppercase tracking-widest text-sky-700 font-semibold">Task</div>
    <p class="mt-3 text-lg text-navy-900 font-semibold">{{ $wr?->prompt }}</p>
    @if($wr?->instructions)<p class="mt-3 text-navy-700">{{ $wr->instructions }}</p>@endif
    <div class="mt-4 flex flex-wrap gap-4 text-sm text-navy-600">
      @if($wr?->minimum_words)<div>Min <strong>{{ $wr->minimum_words }}</strong> words</div>@endif
      @if($wr?->maximum_words)<div>Max <strong>{{ $wr->maximum_words }}</strong> words</div>@endif
      @if($wr?->recommended_time_minutes)<div>~ <strong>{{ $wr->recommended_time_minutes }}</strong> minutes</div>@endif
    </div>
  </div>

  @auth
  <form method="POST" action="{{ route('resources.writing.submit', $resource->slug) }}" class="mt-6 card p-6" x-data="{ content: '', get words(){ return this.content.trim() ? this.content.trim().split(/\s+/).length : 0 } }" data-testid="writing-form">
    @csrf
    <label class="block text-sm font-semibold text-navy-700">Your response</label>
    <textarea name="content" x-model="content" rows="14" class="mt-2 w-full rounded-xl border border-navy-200 p-4 text-navy-900 focus:outline-none focus:ring-2 focus:ring-brand-500/40" placeholder="Start writing…" data-testid="writing-textarea"></textarea>
    <div class="mt-2 flex items-center justify-between text-sm text-navy-600">
      <span data-testid="writing-word-count"><span x-text="words">0</span> / {{ $wr?->minimum_words ?? 0 }} words</span>
      @if($wr?->maximum_words)<span x-show="words > {{ $wr->maximum_words }}" class="text-rose-600">Over the maximum</span>@endif
    </div>
    <div class="mt-4 flex justify-end gap-3">
      <button name="status" value="draft" class="btn-outline" data-testid="writing-save-draft">Save draft</button>
      <button name="status" value="submitted" class="btn-primary" data-testid="writing-submit">Submit</button>
    </div>
  </form>
  @if(session('writing_submission_id'))
    <div class="mt-6 card p-6 bg-emerald-50 border-emerald-200" data-testid="writing-success">
      <p class="font-semibold text-emerald-900">Submission saved.</p>
      <p class="text-sm text-navy-700 mt-1">You can review your submissions from <a href="{{ route('dashboard.writing') }}" class="text-brand-700 font-semibold">My Writing</a>.</p>
    </div>
  @endif
  @else
    <div class="mt-6 card p-6 bg-brand-50 border-brand-100 text-center" data-testid="writing-guest">
      <p class="text-navy-900 font-semibold">Log in or create a free account to submit your writing.</p>
      <div class="mt-4 flex gap-3 justify-center">
        <a href="{{ route('login') }}" class="btn-outline">Log in</a>
        <a href="{{ route('register') }}" class="btn-primary">Create account</a>
      </div>
    </div>
  @endauth

  @if($wr?->example_answer)
    <details class="mt-8">
      <summary class="cursor-pointer text-brand-600 font-semibold">Show example answer</summary>
      <div class="mt-3 prose-content">{!! nl2br(e($wr->example_answer)) !!}</div>
    </details>
  @endif

  @include('pages.resources.partials.related', ['related'=>$related])
</article>
</x-layout>
