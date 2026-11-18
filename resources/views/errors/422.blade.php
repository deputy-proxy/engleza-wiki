<x-layout :robots-index="false" :title="__('The request could not be processed.')">
  <section class="mx-auto max-w-3xl px-6 py-24 text-center">
    <p class="text-sm font-bold uppercase tracking-widest text-brand-600">422</p>
    <h1 class="mt-4 text-3xl font-extrabold text-navy-900">{{ __('The request could not be processed.') }}</h1>
    <p class="mt-3 text-navy-600">{{ __('Check your information and try again.') }}</p>
    <a class="btn-primary mt-8 inline-flex" href="{{ route('home') }}">{{ __('Go home') }}</a>
  </section>
</x-layout>
