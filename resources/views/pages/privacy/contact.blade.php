<x-layout title="Contact privind datele personale | engleza.wiki" :robotsIndex="false">
<section class="mx-auto max-w-3xl px-6 py-12">
<h1 class="text-3xl font-bold text-navy-900">Contact și date personale</h1>
<p class="mt-4 text-navy-700">Pentru acces, export, rectificare sau ștergere, autentifică-te și folosește formularul de solicitări. Solicitările sunt înregistrate și pot fi urmărite în cont.</p>
<div class="mt-6 rounded-2xl border border-navy-100 p-6">
@if($contactEmail)
<p>Adresă de contact: <a class="font-semibold text-brand-600 underline" href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>
@else
<p class="text-sm text-navy-600">Adresa oficială de contact nu este încă configurată. Administratorul trebuie să configureze PRIVACY_CONTACT_EMAIL înainte de aprobarea politicilor.</p>
@endif
@auth
<a class="btn-primary mt-5 inline-flex" href="{{ route('privacy.requests') }}">Solicitările mele</a>
@else
<a class="btn-primary mt-5 inline-flex" href="{{ route('login') }}">Autentificare pentru solicitare</a>
@endauth
</div>
</section>
</x-layout>
