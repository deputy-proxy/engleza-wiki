<x-layout :title="__('Create a class').' — engleza.wiki'">
<section class="mx-auto max-w-2xl px-6 py-12">
  <a href="{{ route('teaching.index') }}" class="text-sm text-brand-700 underline">{{ __('Teaching dashboard') }}</a>
  <h1 class="mt-4 text-3xl font-extrabold text-navy-900">{{ __('Create a class') }}</h1>
  <form method="POST" action="{{ route('teaching.store') }}" class="card mt-6 space-y-4 p-6">
    @csrf
    <label class="block text-sm font-semibold">{{ __('Class name') }}
      <input name="name" required maxlength="120" class="mt-2 w-full rounded-xl border border-navy-200 p-3" value="{{ old('name') }}">
    </label>
    @error('name')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
    @if(auth()->user()->isAdmin())
      <label class="block text-sm font-semibold">{{ __('Assign teacher') }}
        <select name="teacher_id" required class="mt-2 w-full rounded-xl border border-navy-200 p-3">
          <option value="">{{ __('Choose a teacher') }}</option>
          @foreach(\App\Models\User::query()->where('role', 'teacher')->orderBy('name')->get(['id', 'name']) as $teacher)
            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
          @endforeach
        </select>
      </label>
      @error('teacher_id')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
    @endif
    <label class="block text-sm font-semibold">{{ __('Description') }}
      <textarea name="description" rows="3" class="mt-2 w-full rounded-xl border border-navy-200 p-3">{{ old('description') }}</textarea>
    </label>
    <button class="btn-primary">{{ __('Create class') }}</button>
  </form>
</section>
</x-layout>
