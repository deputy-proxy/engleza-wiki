<x-layout title="Politica de cookie-uri | engleza.wiki" :robotsIndex="$policyStatus === 'published'">
<section class="mx-auto max-w-4xl px-6 py-12">
@if($policyStatus === 'draft') @include('pages.policies._draft-notice') @endif
<p class="text-sm font-semibold uppercase tracking-wide text-brand-600">Cookie-uri · Versiunea {{ $policyVersion }}</p>
<h1 class="mt-3 text-4xl font-bold text-navy-900">Politica de cookie-uri</h1>
<div class="prose prose-slate mt-8 max-w-none">
<h2>Cookie-uri necesare</h2><p>Platforma folosește cookie-uri tehnice necesare pentru sesiunea de autentificare, protecția formularelor și funcționarea aplicației. Dezactivarea lor poate afecta funcțiile solicitate.</p>
<h2>Preferințe opționale</h2><p>În versiunea curentă nu este instalat un instrument de analiză sau publicitate și nu sunt încărcate cookie-uri opționale de analiză. Preferința pentru analiză este păstrată pentru o posibilă activare viitoare. Activarea preferinței nu încarcă în prezent niciun script.</p>
<h2>Furnizori externi</h2><p>Fonturile sunt solicitate de la fonts.bunny.net. Browserul poate comunica adresa IP și informații tehnice furnizorului pentru livrarea fonturilor. Găzduirea locală trebuie evaluată înainte de aprobarea politicii.</p>
<h2>Modificarea preferințelor</h2><p>Poți modifica sau retrage preferința oricând din <a href="{{ route('privacy.cookie-settings') }}">setările cookie</a>. Retragerea se aplică încărcărilor viitoare și nu poate șterge date deja prelucrate de un furnizor.</p>
</div>
<p class="mt-10 text-sm text-navy-500">English summary: essential session/security cookies are used. No analytics or advertising tags are installed. The optional analytics preference does not activate a script.</p>
</section>
</x-layout>
