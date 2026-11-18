<x-layout title="Politica de confidențialitate | engleza.wiki" :robotsIndex="$policyStatus === 'published'">
<section class="mx-auto max-w-4xl px-6 py-12">
@if($policyStatus === 'draft') @include('pages.policies._draft-notice') @endif
<p class="text-sm font-semibold uppercase tracking-wide text-brand-600">Confidențialitate · Versiunea {{ $policyVersion }}</p>
<h1 class="mt-3 text-4xl font-bold text-navy-900">Politica de confidențialitate</h1>
<div class="prose prose-slate mt-8 max-w-none">
<h2>1. Operatorul</h2>
<p>Identitatea juridică, sediul și datele complete de contact ale operatorului trebuie completate și verificate înainte de publicarea acestei politici. Pentru solicitări, titularii de cont pot folosi <a href="{{ route('privacy.requests') }}">formularul de solicitări</a> după autentificare și verificarea e-mailului. Consultați și <a href="{{ route('privacy.contact') }}">pagina de contact</a>.</p>
<h2>2. Categorii de date</h2>
<ul>
<li>Date de cont: nume, e-mail, parolă stocată sub formă hash, rol și momentul verificării adresei.</li>
<li>Date educaționale: teste, răspunsuri, rezultate, progres în lecții, exerciții adaptive, vocabular, obiective, realizări și texte trimise.</li>
<li>Date despre clase: clase administrate de profesori, încercări asociate unei clase și rezultatele disponibile profesorului clasei.</li>
<li>Date tehnice: IP și user-agent pentru anumite încercări; identificatori de sesiune și date tehnice temporare.</li>
<li>Date despre solicitări: tip, descriere, stare, verificarea identității și istoricul soluționării.</li>
</ul>
<h2>3. Scopuri și temeiuri</h2>
<p>Datele de cont și de învățare susțin funcțiile solicitate. Datele tehnice pot fi folosite pentru securitate și prevenirea abuzului. Temeiurile juridice concrete, inclusiv orice interes legitim, trebuie confirmate de consilierul juridic înainte de aprobare.</p>
<h2>4. Acces și destinatari</h2>
<p>Utilizatorii își pot consulta propriile date. Profesorii pot vedea numai rezultatele asociate claselor pe care le administrează. Accesul administrativ la lucrările cursanților și la cererile privind datele personale este limitat la administratori. Furnizorii de găzduire, e-mail, fonturi și infrastructură pot prelucra date tehnice; lista, locațiile și contractele trebuie confirmate în registrul furnizorilor.</p>
<h2>5. Drepturile persoanelor</h2>
<p>Poți solicita accesul și exportul, rectificarea sau ștergerea datelor. Solicitările sunt înregistrate și pot fi urmărite în cont. Identitatea este verificată prin autentificare, e-mail verificat și confirmarea parolei curente. Ștergerea nu este automată: trebuie analizate obligațiile legale, evidențele care trebuie păstrate și istoricul educațional care necesită anonimizare sau tratare specială.</p>
<h2>6. Păstrare și securitate</h2>
<p>Perioadele exacte sunt în curs de validare juridică și operațională. Nu se execută ștergeri automate ale încercărilor, certificatelor, lucrărilor sau evidențelor de clasă până la aprobarea matricei de retenție. Măsurile tehnice includ controlul accesului, hash pentru parole, HTTPS și redactarea câmpurilor sensibile din loguri.</p>
<h2>7. Copii de siguranță și transferuri</h2>
<p>Ștergerea din baza activă nu implică dispariția imediată din backup-uri. Frecvența, retenția, locația și procedura de expirare trebuie confirmate cu furnizorul de găzduire. Transferurile în afara SEE și garanțiile aferente trebuie documentate după verificarea contractelor furnizorilor.</p>
<h2>8. Modificări și reclamații</h2>
<p>Versiunea și data intrării în vigoare vor fi publicate după aprobarea juridică. Datele autorității de supraveghere și procedura de reclamație trebuie verificate înainte de publicare.</p>
</div>
<p class="mt-10 text-sm text-navy-500">English summary: this draft covers account, learning, class, technical and request data, user rights, staff access, retention and processor review. It is not an approved privacy notice.</p>
</section>
</x-layout>
