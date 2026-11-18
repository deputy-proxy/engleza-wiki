<x-layout :robots-index="false" :title="__('Saved resources')">
<section class="mx-auto max-w-6xl px-6 py-12">
  <h1 class="text-3xl font-extrabold text-navy-900">{{ __('Saved resources') }}</h1>
  <div class="mt-6 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($saved as $s)
      <a href="{{ route('resources.show',$s->resource->slug) }}" class="card p-6 hover:shadow-lg"><div class="font-bold text-navy-900">{{ $s->resource->translated('title') }}</div><div class="text-sm text-navy-600 mt-1 line-clamp-2">{{ $s->resource->translated('excerpt') }}</div></a>
    @endforeach
  </div>
  <div class="mt-6">{{ $saved->links() }}</div>
</section>
</x-layout>
