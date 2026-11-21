<x-layout :robots-index="false" :title="__('Sign up')">
<section class="mx-auto max-w-md px-6 py-16">
  <h1 class="text-3xl font-extrabold text-navy-900">{{ __('Create your free account') }}</h1>
  <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4 card p-6">
    @csrf
    @if (session('status'))<p class="text-sm text-emerald-700" role="status">{{ session('status') }}</p>@endif
    <div><label for="register-name" class="block text-sm font-semibold text-navy-700">{{ __('Name') }}</label><input id="register-name" @error('name')aria-invalid="true" aria-describedby="register-name-error"@enderror name="name" autocomplete="name" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="register-name"></div>
    <div><label for="register-email" class="block text-sm font-semibold text-navy-700">{{ __('Email') }}</label><input id="register-email" @error('email')aria-invalid="true" aria-describedby="register-email-error"@enderror type="email" name="email" autocomplete="email" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="register-email"></div>
    <div><label for="register-password" class="block text-sm font-semibold text-navy-700">{{ __('Password') }}</label><input id="register-password" @error('password')aria-invalid="true" aria-describedby="register-password-error"@enderror type="password" name="password" autocomplete="new-password" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="register-password"></div>
    <div><label for="register-password_confirmation" class="block text-sm font-semibold text-navy-700">{{ __('Confirm password') }}</label><input id="register-password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="register-password-confirm"></div>
    @error('name')<div id="register-name-error" class="text-sm text-rose-700" role="alert">{{ $message }}</div>@enderror
    @error('email')<div id="register-email-error" class="text-sm text-rose-700" role="alert">{{ $message }}</div>@enderror
    @error('password')<div id="register-password-error" class="text-sm text-rose-700" role="alert">{{ $message }}</div>@enderror
    <p class="text-xs text-navy-500">{{ __('We will send you an email verification link after registration.') }}</p>
    <button type="submit" class="btn-primary w-full" data-testid="register-submit">{{ __('Create account') }}</button>
    <p class="text-center text-sm text-navy-600">{{ __('Have an account? ') }}<a href="{{ route('login') }}" class="font-semibold text-brand-600">{{ __(__('Log in')) }}</a></p>
  </form>
</section>
</x-layout>
