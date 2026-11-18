<x-layout :robots-index="false" :title="__('Verify your email address')">
<section class="mx-auto max-w-md px-6 py-16">
  <h1 class="text-3xl font-extrabold text-navy-900">{{ __('Verify your email address') }}</h1>
  <p class="mt-2 text-sm text-navy-600">{{ __('We sent a verification link to your email address. Open that link to confirm your account.') }}</p>
  @if (session('status'))<p class="mt-4 text-sm text-emerald-700" role="status">{{ session('status') }}</p>@endif
  <form method="POST" action="{{ route('verification.send') }}" class="mt-8 card space-y-4 p-6">
    @csrf
    <button class="btn-primary w-full">{{ __('Resend verification email') }}</button>
  </form>
  <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
    @csrf
    <button class="text-sm font-semibold text-brand-600">{{ __('Log out') }}</button>
  </form>
</section>
</x-layout>
