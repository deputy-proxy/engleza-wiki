<x-layout title="Termeni și condiții | engleza.wiki" :robotsIndex="$policyStatus === 'published'">
<section class="mx-auto max-w-4xl px-6 py-12">
@if($policyStatus === 'draft') @include('pages.policies._draft-notice') @endif
<p class="text-sm font-semibold uppercase tracking-wide text-brand-600">Termeni · Versiunea {{ $policyVersion }}</p>
<h1 class="mt-3 text-4xl font-bold text-navy-900">Termeni și condiții</h1>
<div class="prose prose-slate mt-8 max-w-none">
<h2>1. Despre platformă</h2><p>engleza.wiki oferă resurse educaționale, teste de limba engleză și instrumente de învățare. Identitatea operatorului și datele sale juridice trebuie completate înainte de intrarea în vigoare a acestor termeni.</p>
<h2>2. Conturi și utilizare</h2><p>Utilizatorii trebuie să furnizeze informații corecte, să păstreze confidențialitatea datelor de autentificare și să nu încerce să acceseze conturile, rezultatele sau datele altor persoane. Conturile profesorilor și administratorilor au reguli suplimentare de acces.</p>
<h2>3. Conținut educațional</h2><p>Materialele sunt furnizate în scop educațional. Rezultatele testelor sunt estimări orientative și nu reprezintă certificări oficiale decât dacă o pagină precizează explicit contrariul. Licențele și drepturile asupra conținutului încărcat de utilizatori trebuie completate și verificate.</p>
<h2>4. Conținut trimis de utilizatori</h2><p>Utilizatorii rămân responsabili pentru textele trimise. Nu includeți date personale sensibile ale altor persoane. Accesul personalului la lucrări trebuie limitat la scopurile educaționale și de administrare autorizate.</p>
<h2>5. Disponibilitate și răspundere</h2><p>Serviciul poate fi modificat sau întrerupt pentru mentenanță și securitate. Limitările legale ale răspunderii, procedurile de reclamație și legea aplicabilă trebuie redactate de consilierul juridic pentru operatorul identificat.</p>
<h2>6. Suspendare și încetare</h2><p>Abuzul, compromiterea securității sau încălcarea termenilor poate conduce la restricționarea accesului, cu respectarea drepturilor utilizatorilor și a obligațiilor legale.</p>
<h2>7. Date personale și cookie-uri</h2><p>Prelucrarea datelor este descrisă în <a href="{{ route('privacy.policy') }}">politica de confidențialitate</a>, iar preferințele pot fi modificate din <a href="{{ route('privacy.cookie-settings') }}">setările cookie</a>.</p>
</div>
<p class="mt-10 text-sm text-navy-500">English summary: these draft terms cover accounts, educational content, user submissions, availability and acceptable use. Qualified counsel must review them before publication.</p>
</section>
</x-layout>
