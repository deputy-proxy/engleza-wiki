<x-layout :robots-index="false" :title="__('This action cannot be completed right now.')">
  <section class="mx-auto max-w-3xl px-6 py-24 text-center">
    <p class="text-sm font-bold uppercase tracking-widest text-brand-600">409</p>
    <h1 class="mt-4 text-3xl font-extrabold text-navy-900">{{ __('This action cannot be completed right now.') }}</h1>
    <p class="mt-3 text-navy-600">{{ __('The information may have changed. Refresh the page and try again.') }}</p>
    <a class="btn-primary mt-8 inline-flex" href="{{ route('home') }}">{{ __('Go home') }}</a>
  </section>
</x-layout>
