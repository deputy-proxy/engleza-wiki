<x-filament-widgets::widget>
  <x-filament::section>
    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <h3 class="font-bold text-gray-900 dark:text-white mb-3">Most Popular Tests</h3>
        <ul class="space-y-2">
          @foreach($popularTests as $t)
            <li class="flex justify-between text-sm">
              <span class="text-gray-800 dark:text-gray-200">{{ $t->title }}</span>
              <span class="text-gray-500">{{ $t->attempts_count }} attempts</span>
            </li>
          @endforeach
        </ul>
      </div>
      <div>
        <h3 class="font-bold text-gray-900 dark:text-white mb-3">Most Recent Resources</h3>
        <ul class="space-y-2">
          @foreach($popularResources as $r)
            <li class="flex justify-between text-sm">
              <span class="text-gray-800 dark:text-gray-200">{{ $r->title }}</span>
              <span class="text-gray-500">{{ $r->published_at?->format('M d') }}</span>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </x-filament::section>
</x-filament-widgets::widget>
