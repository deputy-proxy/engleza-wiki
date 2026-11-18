<x-layout title="Setări cookie | engleza.wiki" :robotsIndex="false">
<section class="mx-auto max-w-3xl px-6 py-12">
<h1 class="text-3xl font-bold text-navy-900">Preferințe cookie</h1>
<p class="mt-3 text-navy-600">Cookie-urile strict necesare susțin autentificarea și securitatea. În prezent, platforma nu încarcă instrumente de analiză sau publicitate.</p>
@if(session('status')) <div class="mt-5 rounded-lg bg-green-50 p-4 text-green-800">{{ session('status') }}</div> @endif
<form method="POST" action="{{ route('privacy.cookie-settings.save') }}" class="mt-8 rounded-2xl border border-navy-100 p-6">
@csrf
<div class="flex items-center justify-between gap-4 border-b border-navy-100 pb-4"><div><p class="font-semibold">Cookie-uri necesare</p><p class="text-sm text-navy-500">Sesiune, autentificare și protecție CSRF.</p></div><span class="text-sm font-semibold text-green-700">Mereu active</span></div>
<input type="hidden" name="analytics" value="0">
<label class="mt-5 flex items-start gap-3"><input type="checkbox" name="analytics" value="1" @checked($analytics) class="mt-1 rounded border-navy-300"><span><span class="font-semibold">Analiză opțională</span><span class="block text-sm text-navy-500">Preferința este salvată, dar nu activează niciun script în versiunea actuală.</span></span></label>
<button class="btn-primary mt-6" type="submit">Salvează preferințele</button>
</form>
<p class="mt-5 text-sm text-navy-500">Poți reveni oricând la această pagină din subsolul site-ului. Vezi și <a class="underline" href="{{ route('privacy.cookies') }}">politica de cookie-uri</a>.</p>
</section>
</x-layout>
