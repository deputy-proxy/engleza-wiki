<!DOCTYPE html>
<html lang="en" class="antialiased">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? config('app.name') }}</title>
<meta name="description" content="{{ $description ?? 'Free English resources, tests and lessons for learners and teachers.' }}">
<meta property="og:site_name" content="engleza.wiki">
<meta property="og:title" content="{{ $title ?? config('app.name') }}">
<meta property="og:description" content="{{ $description ?? 'Free English resources and tests.' }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
@vite(['resources/css/app.css','resources/js/app.js'])
@livewireStyles
</head>
<body class="min-h-screen bg-white text-navy-900" data-testid="app-body">
<header class="border-b border-navy-100/70 bg-white/95 backdrop-blur sticky top-0 z-40">
  <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between gap-8">
    <a href="{{ route('home') }}" class="flex items-center gap-2" data-testid="brand-link">
      <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h6a3 3 0 013 3v11a2 2 0 00-2-2H4V5zm16 0h-6a3 3 0 00-3 3v11a2 2 0 012-2h7V5z"/></svg>
      </span>
      <span class="text-xl font-bold tracking-tight text-navy-900">engleza<span class="text-brand-500">.wiki</span></span>
    </a>
    <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-navy-700">
      <a href="{{ route('home') }}" class="hover:text-brand-600" data-testid="nav-home">Home</a>
      <a href="{{ route('resources.index') }}" class="hover:text-brand-600" data-testid="nav-resources">Resources</a>
      <a href="{{ route('grammar') }}" class="hover:text-brand-600" data-testid="nav-grammar">Grammar</a>
      <a href="{{ route('vocabulary') }}" class="hover:text-brand-600" data-testid="nav-vocabulary">Vocabulary</a>
      <a href="{{ route('for-teachers') }}" class="hover:text-brand-600" data-testid="nav-for-teachers">For Teachers</a>
      <a href="{{ route('about') }}" class="hover:text-brand-600" data-testid="nav-about">About</a>
    </nav>
    <div class="flex items-center gap-3">
      <a href="{{ route('search') }}" class="p-2 text-navy-700 hover:text-brand-600" data-testid="search-icon" aria-label="Search">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </a>
      @auth
        <a href="{{ route('dashboard') }}" class="btn-ghost" data-testid="nav-dashboard">Dashboard</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn-outline" data-testid="nav-logout">Log out</button></form>
      @else
        <a href="{{ route('login') }}" class="btn-ghost" data-testid="nav-login">Log in</a>
        <a href="{{ route('tests.index') }}" class="btn-primary" data-testid="nav-cta-test">Test your English</a>
      @endauth
    </div>
  </div>
</header>

<main data-testid="main-content">
  {{ $slot }}
</main>

<footer class="mt-20 border-t border-navy-100/70 bg-navy-50/50">
  <div class="mx-auto max-w-7xl px-6 py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
    <div class="flex items-center gap-2">
      <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h6a3 3 0 013 3v11a2 2 0 00-2-2H4V5zm16 0h-6a3 3 0 00-3 3v11a2 2 0 012-2h7V5z"/></svg>
      </span>
      <span class="font-semibold text-navy-900">engleza.wiki</span>
    </div>
    <p class="text-sm text-navy-600">Free resources for learners, teachers and curious minds.</p>
    <div class="text-sm text-navy-600">© {{ date('Y') }} engleza.wiki</div>
  </div>
</footer>

@livewireScripts
</body>
</html>
