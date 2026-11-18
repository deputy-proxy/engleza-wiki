<x-layout :robots-index="false" :title="__('Forgot password')">
<section class="mx-auto max-w-md px-6 py-16">
  <h1 class="text-3xl font-extrabold text-navy-900">{{ __('Reset your password') }}</h1>
  <p class="mt-2 text-sm text-navy-600">{{ __('Enter the email address associated with your account. If an account exists, we will send a reset link.') }}</p>
  @if (session('status'))<p class="mt-4 text-sm text-emerald-700" role="status">{{ session('status') }}</p>@endif
  <form method="POST" action="{{ route('password.email') }}" class="mt-8 card space-y-4 p-6">
    @csrf
    <div>
      <label for="email" class="block text-sm font-semibold text-navy-700">{{ __('Email') }}</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm">
      @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>
    <button class="btn-primary w-full">{{ __('Send password reset link') }}</button>
    <p class="text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-brand-600">{{ __('Back to log in') }}</a></p>
  </form>
</section>
</x-layout>
