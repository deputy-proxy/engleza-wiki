<x-layout :title="'Sign up'">
<section class="mx-auto max-w-md px-6 py-16">
  <h1 class="text-3xl font-extrabold text-navy-900">Create your free account</h1>
  <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4 card p-6">
    @csrf
    <div><label class="block text-sm font-semibold text-navy-700">Name</label><input name="name" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="register-name"></div>
    <div><label class="block text-sm font-semibold text-navy-700">Email</label><input type="email" name="email" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="register-email"></div>
    <div><label class="block text-sm font-semibold text-navy-700">Password</label><input type="password" name="password" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="register-password"></div>
    <div><label class="block text-sm font-semibold text-navy-700">Confirm password</label><input type="password" name="password_confirmation" required class="mt-1 w-full rounded-xl border border-navy-200 px-4 py-3 text-sm" data-testid="register-password-confirm"></div>
    @error('name')<div class="text-sm text-rose-600">{{ $message }}</div>@enderror
    @error('email')<div class="text-sm text-rose-600">{{ $message }}</div>@enderror
    @error('password')<div class="text-sm text-rose-600">{{ $message }}</div>@enderror
    <button class="btn-primary w-full" data-testid="register-submit">Create account</button>
    <p class="text-center text-sm text-navy-600">Have an account? <a href="{{ route('login') }}" class="font-semibold text-brand-600">Log in</a></p>
  </form>
</section>
</x-layout>
