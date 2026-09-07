<x-layout :title="'engleza.wiki – Free English Resources for Learners and Teachers'" :description="'Everything you need to learn and teach English — for free. Grammar, vocabulary, lessons, worksheets and a free CEFR level test.'">

<section class="relative overflow-hidden group">
  <div class="mx-auto max-w-7xl px-6 pt-16 pb-20 grid lg:grid-cols-12 gap-12 items-center">
    
    <!-- Left Column with stagger-reveal animations -->
    <div class="lg:col-span-7">
      
      <!-- Eyebrow: Pulsing and slightly tracking on hover -->
      <div 
        class="inline-flex items-center gap-3 text-sm font-semibold text-brand-600 uppercase tracking-wider transition-all duration-700 ease-out hover:tracking-widest" 
        data-testid="hero-eyebrow"
      >
        <span>Learn</span>
        <span class="h-1 w-1 rounded-full bg-brand-500 animate-pulse"></span>
        <span>Teach</span>
        <span class="h-1 w-1 rounded-full bg-brand-500 animate-pulse delay-150"></span>
        <span>Grow</span>
      </div>

      <!-- Heading with title-scale on hover -->
      <h1 class="mt-4 text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight text-navy-900" data-testid="hero-title">
        Everything you need<br>to learn and teach<br>
        English — <span class="text-brand-500 inline-block transition-transform duration-300 hover:scale-105">for free.</span>
      </h1>

      <!-- Subtitle -->
      <p class="mt-6 max-w-xl text-lg text-navy-600" data-testid="hero-subtitle">
        engleza.wiki is your go-to resource for free, high-quality materials,
        tips and tools to make English learning and teaching easier, more effective
        and more enjoyable.
      </p>

      <!-- Live search with instant suggestions -->
      <div class="mt-8">
        <livewire:home-search />
      </div>

      <!-- Action Buttons with translate hover and icon move -->
      <div class="mt-8 flex flex-wrap gap-4">
        <a href="{{ route('resources.index') }}" class="btn-outline transition-transform duration-300 hover:-translate-y-0.5" data-testid="hero-explore-btn">
          Explore Resources
        </a>
        <a href="{{ route('tests.index') }}" class="btn-ghost text-brand-600 group inline-flex items-center gap-1" data-testid="hero-take-test-btn">
          Take the free English test 
          <span class="transition-transform duration-200 group-hover:translate-x-1">→</span>
        </a>
      </div>
    </div>

    <!-- Right Column: Interactive 3D Card with 3 cards layout -->
    <div 
      class="lg:col-span-5 [perspective:1000px]"
    >
      <div 
        id="interactive-3d-card"
        class="relative aspect-square rounded-[2rem] bg-gradient-to-br from-brand-50 via-white to-yellow-50 p-8 ring-1 ring-navy-100/60 shadow-[0_20px_60px_-20px_rgba(15,30,92,0.25)] transition-transform duration-200 ease-out [transform-style:preserve-3d]"
      >
        
        <!-- The Inner Floating Cards Layout (replicates image composition) -->
        <div class="absolute inset-6 rounded-3xl bg-white shadow-inner p-6 flex flex-col gap-4 [transform:translateZ(30px)]">
          
          <!-- Inner Card 1 (Top Left) -->
          <div class="rounded-2xl bg-brand-50 p-4 w-[60%] [transform:translateZ(10px)]">
            <div class="text-xs font-semibold text-brand-600 uppercase">Grammar</div>
            <div class="mt-3 text-2xl font-bold text-navy-900">Tenses</div>
            <div class="mt-2 text-xs text-navy-600">Master past, present, future</div>
          </div>

          <!-- Inner Card 2 (Middle Right) -->
          <div class="rounded-2xl bg-yellow-50 p-4 w-[60%] self-end -mt-6 [transform:translateZ(15px)]">
            <div class="text-xs font-semibold text-yellow-700 uppercase">Vocabulary</div>
            <div class="mt-3 text-2xl font-bold text-navy-900">Travel</div>
            <div class="mt-2 text-xs text-navy-600">100+ practical words</div>
          </div>

          <!-- Inner Card 3 (Bottom Left) -->
          <div class="rounded-2xl bg-emerald-50 p-4 w-[60%] -mt-6 [transform:translateZ(20px)]">
            <div class="text-xs font-semibold text-emerald-700 uppercase">Practice</div>
            <div class="mt-3 text-2xl font-bold text-navy-900">Speaking</div>
            <div class="mt-2 text-xs text-navy-600">Everyday conversations</div>
          </div>

        </div>
      </div>
    </div>

  </div>
</section>

<!-- Include script once per page for 3D card tilt -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const card = document.getElementById('interactive-3d-card');
    if (!card) return;

    function handleMove(e) {
      const rect = card.getBoundingClientRect();
      const clientX = e.touches ? e.touches[0].clientX : e.clientX;
      const clientY = e.touches ? e.touches[0].clientY : e.clientY;

      const x = clientX - rect.left;
      const y = clientY - rect.top;

      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      // Max rotation angles (degrees)
      const rotateX = ((y - centerY) / centerY) * -10;
      const rotateY = ((x - centerX) / centerX) * 10;

      card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    }

    function handleReset() {
      card.style.transform = 'rotateX(0deg) rotateY(0deg)';
    }

    card.addEventListener('mousemove', handleMove);
    card.addEventListener('mouseleave', handleReset);
    card.addEventListener('touchmove', handleMove, { passive: true });
    card.addEventListener('touchend', handleReset);
  });
</script>

<!-- Lightweight Script for 3D Tilt Interaction -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const card = document.getElementById('interactive-3d-card');
    if (!card) return;

    function handleMove(e) {
      const rect = card.getBoundingClientRect();
      const clientX = e.touches ? e.touches[0].clientX : e.clientX;
      const clientY = e.touches ? e.touches[0].clientY : e.clientY;

      const x = clientX - rect.left;
      const y = clientY - rect.top;

      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      // Max rotation angles (degrees)
      const rotateX = ((y - centerY) / centerY) * -12;
      const rotateY = ((x - centerX) / centerX) * 12;

      card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
    }

    function handleReset() {
      card.style.transform = 'rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
    }

    // Mouse Events
    card.addEventListener('mousemove', handleMove);
    card.addEventListener('mouseleave', handleReset);

    // Touch Events for Mobile
    card.addEventListener('touchmove', handleMove, { passive: true });
    card.addEventListener('touchend', handleReset);
  });
</script>

<section class="border-t border-navy-100/70 bg-navy-50/40">
  <div class="mx-auto max-w-7xl px-6 py-14 grid md:grid-cols-2 lg:grid-cols-4 gap-6" data-testid="value-strip">
    @foreach ([
      ['bg-emerald-500','Free Resources','Access printable worksheets, exercises, guides and more — 100% free.'],
      ['bg-brand-500','For All Levels','From beginners to advanced learners, find resources for every stage.'],
      ['bg-yellow-500','For Teachers & Parents','Practical tools, lesson plans and tips to support teaching.'],
      ['bg-purple-500','Trusted & Practical','Well-organized, easy to use, and based on real classroom experience.'],
    ] as [$bg,$t,$d])
      <div class="card p-6">
        <div class="h-10 w-10 rounded-xl {{ $bg }} text-white flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h3 class="mt-4 text-base font-bold text-navy-900">{{ $t }}</h3>
        <p class="mt-1 text-sm text-navy-600">{{ $d }}</p>
      </div>
    @endforeach
  </div>
</section>

<section class="py-20">
  <div class="mx-auto max-w-7xl px-6">
    <div class="flex items-end justify-between gap-6">
      <div>
        <div class="text-xs font-semibold uppercase tracking-widest text-brand-600">Explore our resources</div>
        <h2 class="mt-2 text-3xl md:text-4xl font-extrabold text-navy-900">Popular Categories</h2>
      </div>
      <a href="{{ route('resources.index') }}" class="btn-ghost text-brand-600">View all resources →</a>
    </div>
    <div class="mt-10 grid md:grid-cols-2 lg:grid-cols-3 gap-6" data-testid="categories-grid">
      @php
      $palette = [
        'grammar'=>['bg-brand-50','text-brand-700','📖'],
        'vocabulary'=>['bg-emerald-50','text-emerald-700','💬'],
        'listening'=>['bg-purple-50','text-purple-700','🎧'],
        'speaking'=>['bg-yellow-50','text-yellow-700','🗣️'],
        'reading'=>['bg-rose-50','text-rose-700','📚'],
        'writing'=>['bg-sky-50','text-sky-700','✍️'],
        'lesson-plans'=>['bg-teal-50','text-teal-700','💡'],
      ];
      @endphp
      @foreach ($categories as $cat)
        @php $p = $palette[$cat->slug] ?? ['bg-navy-50','text-navy-700','📘']; @endphp
        <a href="{{ route('categories.show',$cat->slug) }}" class="group card p-6 {{ $p[0] }} hover:-translate-y-0.5 transition" data-testid="category-card-{{ $cat->slug }}">
          <div class="text-3xl">{{ $p[2] }}</div>
          <h3 class="mt-4 text-xl font-bold {{ $p[1] }}">{{ $cat->name }}</h3>
          <p class="mt-2 text-sm text-navy-600">{{ $cat->description ?? 'Explore this category' }}</p>
          <div class="mt-6 inline-flex items-center gap-1 text-sm font-semibold {{ $p[1] }}">Browse →</div>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="py-16 bg-gradient-to-br from-navy-900 via-navy-800 to-brand-700 text-white">
  <div class="mx-auto max-w-4xl px-6 text-center" data-testid="test-cta">
    <h2 class="text-3xl md:text-5xl font-extrabold">How good is your English?</h2>
    <p class="mt-4 text-lg text-white/80">Take a free English level test and discover your approximate CEFR level in minutes.</p>
    <a href="{{ route('tests.index') }}" class="mt-8 inline-flex btn-primary bg-white text-brand-700 hover:bg-yellow-50" data-testid="take-free-test-btn">Take the free test</a>
  </div>
</section>

<section class="py-20">
  <div class="mx-auto max-w-7xl px-6">
    <h2 class="text-3xl font-extrabold text-navy-900">Featured resources</h2>
    <div class="mt-8 grid md:grid-cols-2 lg:grid-cols-3 gap-6" data-testid="featured-resources">
      @foreach ($featured as $r)
        <a href="{{ route('resources.show',$r->slug) }}" class="card p-6 hover:shadow-lg transition" data-testid="resource-card-{{ $r->slug }}">
          <div class="flex items-center gap-2 text-xs">
            @if($r->level)<span class="chip">{{ $r->level->name }}</span>@endif
            <span class="chip bg-brand-50 text-brand-700">{{ ucfirst(str_replace('_',' ',$r->type)) }}</span>
          </div>
          <h3 class="mt-4 text-lg font-bold text-navy-900">{{ $r->title }}</h3>
          <p class="mt-2 text-sm text-navy-600 line-clamp-3">{{ $r->excerpt }}</p>
          <div class="mt-4 text-sm font-semibold text-brand-600">Read →</div>
        </a>
      @endforeach
    </div>
  </div>
</section>

</x-layout>
