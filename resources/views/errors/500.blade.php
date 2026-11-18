<x-layout :robots-index="false" :title="__('Something went wrong on our side. Please try again.')">
  <section class="mx-auto max-w-3xl px-6 py-24 text-center">
    <p class="text-sm font-bold uppercase tracking-widest text-brand-600">500</p>
    <h1 class="mt-4 text-3xl font-extrabold text-navy-900">{{ __('Something went wrong on our side. Please try again.') }}</h1>
    <a class="btn-primary mt-8 inline-flex" href="{{ route('home') }}">{{ __('Go home') }}</a>
  </section>
</x-layout>
