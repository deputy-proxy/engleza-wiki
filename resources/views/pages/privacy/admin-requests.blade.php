<x-layout title="Administrare solicitări privind datele | engleza.wiki" :robotsIndex="false">
<section class="mx-auto max-w-6xl px-6 py-12">
<h1 class="text-3xl font-bold text-navy-900">Solicitări privind datele personale</h1>
<p class="mt-2 text-sm text-navy-600">Acces limitat administratorilor. Nu marca o solicitare de ștergere ca finalizată înainte de verificarea retenției, backup-urilor și obligațiilor legale.</p>
@if(session('status')) <div class="mt-5 rounded-lg bg-green-50 p-4 text-green-800">{{ session('status') }}</div> @endif
<div class="mt-8 space-y-5">
@foreach($requests as $item)
<article class="rounded-xl border border-navy-100 p-5">
<div class="flex flex-wrap justify-between gap-3"><div><p class="font-semibold">{{ $item->user->name }} · {{ $item->user->email }}</p><p class="text-sm text-navy-500">#{{ $item->id }} · {{ $item->request_type }} · {{ $item->created_at->format('d.m.Y H:i') }}</p></div><span class="rounded-full bg-navy-50 px-3 py-1 text-sm">{{ $item->status }}</span></div>
<p class="mt-3 whitespace-pre-wrap text-sm">{{ $item->description ?: 'Fără detalii suplimentare.' }}</p>
<p class="mt-2 text-xs text-navy-500">Identitate verificată: {{ $item->identity_verified_at?->format('d.m.Y H:i') ?? 'Nu' }}</p>
<form method="POST" action="{{ route('admin.privacy.requests.update', $item) }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_2fr_auto]">
@csrf @method('PUT')
<select name="status" class="rounded-lg border border-navy-200 p-2">
@foreach(['submitted' => 'Nouă', 'in_review' => 'În analiză', 'completed' => 'Finalizată', 'rejected' => 'Respinsă'] as $value => $label)
<option value="{{ $value }}" @selected($item->status === $value)>{{ $label }}</option>
@endforeach
</select>
<textarea name="admin_notes" rows="2" class="rounded-lg border border-navy-200 p-2" placeholder="Notițe interne (nu sunt incluse în export)">{{ $item->admin_notes }}</textarea>
<button class="btn-primary" type="submit">Actualizează</button>
</form>
</article>
@endforeach
</div>
<div class="mt-5">{{ $requests->links() }}</div>
</section>
</x-layout>
