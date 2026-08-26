<x-layout :title="'Log in'">
<section class="mx-auto max-w-md px-6 py-16">
  <h1 class="text-3xl font-extrabold text-navy-900">Welcome back</h1>
  <p class="text-navy-600 mt-1">Log in to track your progress.</p>
  <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4 card p-6">
    @csrf
    <div><label class="block text-sm font-semibold text-navy-700">Email</label><input type="email" name="email" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="login-email"></div>
    <div><label class="block text-sm font-semibold text-navy-700">Password</label><input type="password" name="password" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="login-password"></div>
    @error('email')<div class="text-sm text-rose-600" data-testid="login-error">{{ $message }}</div>@enderror
    <button class="btn-primary w-full" data-testid="login-submit">Log in</button>
    <p class="text-center text-sm text-navy-600">No account? <a href="{{ route('register') }}" class="font-semibold text-brand-600">Sign up</a></p>
  </form>
</section>
</x-layout>
