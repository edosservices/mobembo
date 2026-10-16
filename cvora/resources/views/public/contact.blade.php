<x-public-layout>
    <div class="mx-auto max-w-xl px-4 py-14">
        <h1 class="font-display text-4xl font-semibold">Contact</h1>
        <p class="mt-3 text-sm text-slate-600">Écrivez-nous à {{ \App\Models\Setting::getValue('contact.email', 'contact@cvora.app') }} ou utilisez le formulaire.</p>
        @if (session('status'))
            <p class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-4">
            @csrf
            <div class="hidden" aria-hidden="true"><label>Site web<input name="website" tabindex="-1" autocomplete="off"></label></div>
            <label class="block text-sm font-medium">Nom<input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block text-sm font-medium">E-mail<input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block text-sm font-medium">Message<textarea name="message" rows="5" required class="mt-1 w-full rounded-xl border-slate-300">{{ old('message') }}</textarea></label>
            @foreach ($errors->all() as $error)
                <p class="text-sm text-red-600">{{ $error }}</p>
            @endforeach
            <button class="rounded-full bg-brand px-5 py-3 text-sm font-semibold text-white">Envoyer</button>
        </form>
    </div>
</x-public-layout>
