<x-layout :title="'Your result – '.$test->title">
@php $r = $attempt->result; @endphp
<section class="mx-auto max-w-3xl px-6 py-12">
  <div class="card p-10 text-center">
    <div class="text-sm font-semibold uppercase text-brand-600 tracking-widest">Your English level</div>
    <div class="mt-3 text-7xl font-extrabold text-navy-900" data-testid="result-level">{{ $r?->level ?? $attempt->level_result ?? '—' }}</div>
    <div class="mt-3 text-xl text-navy-600">Overall score: <span class="font-bold text-navy-900" data-testid="result-percentage">{{ $r?->overall_percentage ?? $attempt->percentage }}%</span></div>
    <div class="mt-2 text-sm text-navy-500">{{ $r?->overall_score ?? $attempt->score }} points</div>
    @if($attempt->status === 'completed')
      <a href="{{ route('tests.certificate', ['test'=>$test->slug, 'attempt'=>$attempt->id]) }}" class="mt-6 inline-flex btn-primary bg-emerald-600 hover:bg-emerald-700" data-testid="download-certificate-btn">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Download certificate (PDF)
      </a>
    @endif
  </div>

  <h2 class="mt-10 text-2xl font-bold text-navy-900">Performance breakdown</h2>
  <div class="mt-4 space-y-3" data-testid="result-breakdown">
    @foreach ([['Grammar',$r?->grammar_score],['Vocabulary',$r?->vocabulary_score],['Reading',$r?->reading_score],['Writing',$r?->writing_score],['Listening',$r?->listening_score],['Speaking',$r?->speaking_score]] as [$name,$val])
      @if($val !== null)
        <div>
          <div class="flex justify-between text-sm mb-1"><span class="font-semibold text-navy-900">{{ $name }}</span><span class="text-navy-600">{{ $val }}%</span></div>
          <div class="h-2.5 w-full rounded-full bg-navy-100 overflow-hidden"><div class="h-full {{ $val>=70?'bg-emerald-500':($val>=50?'bg-yellow-500':'bg-rose-500') }}" style="width: {{ $val }}%"></div></div>
        </div>
      @endif
    @endforeach
  </div>

  @if($recommendations->isNotEmpty())
  <h2 class="mt-10 text-2xl font-bold text-navy-900">Recommended for you</h2>
  <p class="text-sm text-navy-600 mt-1">Based on the areas where you can improve.</p>
  <div class="mt-4 grid md:grid-cols-2 gap-4" data-testid="result-recommendations">
    @foreach($recommendations as $rec)
      <a href="{{ route('resources.show',$rec->slug) }}" class="card p-5 hover:shadow-lg transition">
        <div class="text-xs text-brand-600 font-semibold">{{ ucfirst(str_replace('_',' ',$rec->type)) }}</div>
        <div class="mt-2 font-bold text-navy-900">{{ $rec->title }}</div>
        <div class="mt-1 text-sm text-navy-600 line-clamp-2">{{ $rec->excerpt }}</div>
      </a>
    @endforeach
  </div>
  @endif

  @guest
  <div class="mt-10 card p-6 bg-brand-50 border border-brand-100 text-center">
    <p class="text-navy-900 font-semibold">Create a free account to save your result and track your progress.</p>
    <div class="mt-4 flex gap-3 justify-center">
      <a href="{{ route('register') }}" class="btn-primary" data-testid="result-register-btn">Create free account</a>
      <a href="{{ route('login') }}" class="btn-outline">Log in</a>
    </div>
  </div>
  @endguest
</section>
</x-layout>
