<x-layout :robots-index="$robotsIndex" :title="$category->translated('name').' – engleza.wiki'" :description="$category->translated('description') ?: 'Explorează resurse de învățare a limbii engleze din această categorie.'">
<section class="mx-auto max-w-7xl px-6 py-12">
  <h1 class="text-4xl font-extrabold text-navy-900" data-testid="category-title">{{ $category->translated('name') }}</h1>
  <p class="mt-2 text-navy-600">{{ $category->translated('description') }}</p>
  <div class="mt-10 grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($resources as $r)
      <a href="{{ route('resources.show',$r->slug) }}" class="card p-6 hover:shadow-lg transition">
        <h3 class="text-lg font-bold text-navy-900">{{ $r->translated('title') }}</h3>
        <p class="mt-2 text-sm text-navy-600 line-clamp-3">{{ $r->translated('excerpt') }}</p>
      </a>
    @empty
      <div class="col-span-full text-navy-600">{{ __('No resources yet.') }}</div>
    @endforelse
  </div>
  <div class="mt-8">{{ $resources->links() }}</div>
</section>
</x-layout>
