<x-layout title="Solicitările mele privind datele | engleza.wiki" :robotsIndex="false">
<section class="mx-auto max-w-4xl px-6 py-12">
<h1 class="text-3xl font-bold text-navy-900">Solicitările mele privind datele</h1>
<p class="mt-3 text-navy-600">Fiecare solicitare necesită adresa de e-mail verificată și parola curentă.</p>
@if(session('status')) <div class="mt-5 rounded-lg bg-green-50 p-4 text-green-800">{{ session('status') }}</div> @endif
<form method="POST" action="{{ route('privacy.requests.store') }}" class="mt-8 space-y-5 rounded-2xl border border-navy-100 p-6">
@csrf
<div><label class="mb-2 block font-semibold" for="request_type">Tipul solicitării</label>
<select id="request_type" name="request_type" required class="w-full rounded-lg border border-navy-200 p-3">
<option value="access">Acces și export al datelor</option><option value="correction">Rectificarea datelor</option><option value="deletion">Ștergerea contului și a datelor</option>
</select></div>
<div><label class="mb-2 block font-semibold" for="description">Detalii (obligatoriu pentru rectificare și ștergere)</label>
<textarea id="description" name="description" rows="4" maxlength="5000" class="w-full rounded-lg border border-navy-200 p-3" placeholder="Descrie datele sau solicitarea. Nu include parole ori date sensibile ale altor persoane.">{{ old('description') }}</textarea>
@error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror</div>
<div><label class="mb-2 block font-semibold" for="current_password">Parola curentă</label>
<input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-navy-200 p-3">
@error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror</div>
<p class="text-sm text-navy-500">Exportul este generat imediat ca fișier JSON. Cererile de rectificare și ștergere sunt revizuite de un administrator; istoricul educațional nu este șters automat.</p>
<button class="btn-primary" type="submit">Trimite solicitarea</button>
</form>
<h2 class="mt-10 text-xl font-bold">Istoric</h2>
<div class="mt-4 overflow-x-auto rounded-xl border border-navy-100"><table class="w-full text-left text-sm">
<thead class="bg-navy-50"><tr><th class="p-3">Data</th><th class="p-3">Tip</th><th class="p-3">Stare</th><th class="p-3">Descriere</th></tr></thead><tbody>
@forelse($requests as $item)
<tr class="border-t border-navy-100"><td class="p-3">{{ $item->created_at->format('d.m.Y H:i') }}</td><td class="p-3">{{ ['access' => 'Acces/export', 'correction' => 'Rectificare', 'deletion' => 'Ștergere'][$item->request_type] ?? $item->request_type }}</td><td class="p-3">{{ $item->status }}</td><td class="p-3">{{ $item->description ?: '—' }}</td></tr>
@empty
<tr><td colspan="4" class="p-4 text-navy-500">Nu ai trimis încă solicitări.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $requests->links() }}</div>
</section>
</x-layout>
