<x-layout :title="'Taking '.$test->title.' – engleza.wiki'">
<section class="mx-auto max-w-3xl px-6 py-8">
  <livewire:tests.take-test :test="$test" :attempt="$attempt" />
</section>
</x-layout>
