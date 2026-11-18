<x-layout :robots-index="false" :title="__('Choose a new password')">
<section class="mx-auto max-w-md px-6 py-16">
  <h1 class="text-3xl font-extrabold text-navy-900">{{ __('Choose a new password') }}</h1>
  <form method="POST" action="{{ route('password.update') }}" class="mt-8 card space-y-4 p-6">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
      <label for="email" class="block text-sm font-semibold text-navy-700">{{ __('Email') }}</label>
      <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm">
      @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
      <label for="password" class="block text-sm font-semibold text-navy-700">{{ __('New password') }}</label>
      <input id="password" type="password" name="password" required autocomplete="new-password" class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm">
      @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
      <label for="password_confirmation" class="block text-sm font-semibold text-navy-700">{{ __('Confirm new password') }}</label>
      <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm">
    </div>
    <button class="btn-primary w-full">{{ __('Reset password') }}</button>
  </form>
</section>
</x-layout>
