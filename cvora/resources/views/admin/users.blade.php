<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Comptes</h1></x-slot>
    <div class="space-y-3">
        @foreach ($users as $account)
            <form method="POST" action="{{ route('admin.users.update', $account) }}" class="grid gap-2 rounded-2xl bg-white p-4 shadow-sm md:grid-cols-6 md:items-center">
                @csrf @method('PUT')
                <div class="md:col-span-2"><p class="font-medium">{{ $account->name }}</p><p class="text-xs text-slate-500">{{ $account->email }} · {{ $account->resumes_count }} CV · {{ $account->cover_letters_count }} lettres</p></div>
                <select name="role" class="rounded-lg border-slate-300"><option value="user" @selected($account->role->value === 'user')>Utilisateur</option><option value="admin" @selected($account->role->value === 'admin')>Administrateur</option></select>
                <select name="is_active" class="rounded-lg border-slate-300"><option value="1" @selected($account->is_active)>Actif</option><option value="0" @selected(! $account->is_active)>Inactif</option></select>
                <input name="bonus_credits" type="number" min="0" value="{{ $account->currentSubscription?->bonus_credits ?? 0 }}" class="rounded-lg border-slate-300" aria-label="Crédits bonus">
                <button class="rounded-full bg-brand px-3 py-2 text-sm font-semibold text-white">Enregistrer</button>
            </form>
        @endforeach
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>
