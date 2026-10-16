<x-public-layout>
    <div class="mx-auto max-w-6xl px-4 py-14">
        <h1 class="font-display text-4xl font-semibold">Tarifs</h1>
        <p class="mt-3 max-w-2xl text-slate-600">Gratuit, Premium et Pro sont des offres configurables. Si un prix payant n’est pas encore saisi, il s’affiche comme « Tarif à définir ».</p>
        <div class="mt-8 grid gap-4 lg:grid-cols-3">
            @forelse ($plans as $plan)
                <article class="rounded-2xl border border-slate-200 p-6">
                    <h2 class="font-display text-2xl font-semibold">{{ $plan->name }}</h2>
                    <p class="mt-2 text-3xl font-semibold text-brand">{{ \App\Support\Money::format($plan->price_cents, $plan->currency) }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $plan->description }}</p>
                    <a href="{{ route('register') }}" class="mt-6 inline-flex rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Commencer</a>
                </article>
            @empty
                <p>Les offres seront publiées après la configuration administrateur.</p>
            @endforelse
        </div>
    </div>
</x-public-layout>
