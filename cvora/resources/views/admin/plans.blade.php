<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Offres et quotas</h1></x-slot>
    @foreach ($plans as $plan)
        <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="mb-4 space-y-3 rounded-2xl bg-white p-5 shadow-sm">
            @csrf @method('PUT')
            <input name="name" value="{{ $plan->name }}" class="w-full rounded-xl border-slate-300">
            <textarea name="description" class="w-full rounded-xl border-slate-300">{{ $plan->description }}</textarea>
            <div class="grid gap-2 sm:grid-cols-3">
                <input name="price_cents" type="number" min="0" value="{{ $plan->price_cents }}" placeholder="Prix en centimes, vide si non défini" class="rounded-xl border-slate-300">
                <select name="currency" class="rounded-xl border-slate-300"><option value="CDF" @selected($plan->currency === 'CDF')>CDF</option><option value="USD" @selected($plan->currency === 'USD')>USD</option></select>
                <select name="is_active" class="rounded-xl border-slate-300"><option value="1" @selected($plan->is_active)>Active</option><option value="0">Inactive</option></select>
            </div>
            <select name="is_public" class="rounded-xl border-slate-300"><option value="1" @selected($plan->is_public)>Publique</option><option value="0">Masquée</option></select>
            <textarea name="limits" rows="8" class="w-full rounded-xl border-slate-300 font-mono text-xs">{{ json_encode($plan->limits(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
            <button class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Enregistrer {{ $plan->name }}</button>
        </form>
    @endforeach
</x-app-layout>
