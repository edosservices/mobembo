<x-app-layout>
    <x-slot name="header"><h1 class="font-display text-2xl font-semibold">Importer un CV</h1></x-slot>
    <form method="POST" action="{{ route('resumes.import.store') }}" enctype="multipart/form-data" class="max-w-xl rounded-2xl bg-white p-6 shadow-sm">
        @csrf
        <p class="text-sm text-slate-600">PDF ou DOCX, 5 Mo maximum. Le fichier est lu comme du texte. Vous confirmez les champs avant tout enregistrement. Un PDF scanné sans texte sera signalé : la reconnaissance optique n’est pas encore branchée.</p>
        <input type="file" name="file" accept=".pdf,.docx" required class="mt-4 block w-full text-sm">
        @foreach ($errors->all() as $error)
            <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
        @endforeach
        <button class="mt-4 rounded-full bg-brand px-4 py-2 text-sm font-semibold text-white">Lire le fichier</button>
    </form>
</x-app-layout>
