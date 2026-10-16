<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Journaux techniques</h1></x-slot>
    <ul class="space-y-2 text-sm">
        @foreach ($events as $event)
            <li class="rounded-xl bg-white px-4 py-3"><span class="font-medium">{{ $event->level }}</span> · {{ $event->source }} · {{ $event->message }}<br><span class="text-slate-500">{{ $event->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span></li>
        @endforeach
    </ul>
    <div class="mt-4">{{ $events->links() }}</div>
</x-app-layout>
