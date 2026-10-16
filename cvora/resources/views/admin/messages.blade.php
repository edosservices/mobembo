<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Messages de contact</h1></x-slot>
    <ul class="space-y-3">
        @foreach ($messages as $message)
            <li class="rounded-2xl bg-white p-4 text-sm shadow-sm"><p class="font-medium">{{ $message->name }} · {{ $message->email }}</p><p class="mt-1">{{ $message->message }}</p></li>
        @endforeach
    </ul>
</x-app-layout>
