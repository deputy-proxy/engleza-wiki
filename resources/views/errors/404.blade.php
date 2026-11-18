<x-layout :title="__('Page not found')">
  <section class="mx-auto max-w-3xl px-6 py-24 text-center">
    <p class="text-sm font-bold uppercase tracking-widest text-brand-600">404</p>
    <h1 class="mt-4 text-3xl font-extrabold text-navy-900">{{ __('Page not found') }}</h1>
    <p class="mt-3 text-navy-600">{{ __('The page you are looking for could not be found.') }}</p>
    <a class="btn-primary mt-8 inline-flex" href="{{ route('home') }}">{{ __('Go home') }}</a>
  </section>
</x-layout>
