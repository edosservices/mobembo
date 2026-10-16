<x-app-layout>
    <div x-data="resumeEditor()" data-save-url="{{ route('resumes.autosave', $resume) }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_380px]">
        <script id="resume-state" type="application/json">@json($state + ['preview' => route('resumes.preview', $resume)])</script>
        <section class="rounded-2xl bg-white p-4 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <label class="block grow text-sm font-medium">Titre du document
                    <input x-model="title" class="mt-1 w-full rounded-xl border-slate-300">
                </label>
                <p class="text-sm text-slate-500" x-text="status" role="status"></p>
            </div>
            <div class="mt-4 flex gap-2 overflow-x-auto pb-2">
                <template x-for="(label, index) in steps" :key="label">
                    <button type="button" class="shrink-0 rounded-full px-3 py-1 text-sm" :class="step === index ? 'bg-brand text-white' : 'bg-mist'" @click="step = index" x-text="label"></button>
                </template>
            </div>

            <div class="mt-4 space-y-3" x-show="step === 0">
                <template x-for="field in ['first_name','last_name','title','email','phone','city','country','address','linkedin','website']" :key="field">
                    <label class="block text-sm capitalize"><span x-text="field.replace('_', ' ')"></span>
                        <input class="mt-1 w-full rounded-xl border-slate-300" x-model="content.personal[field]">
                    </label>
                </template>
            </div>
            <div class="mt-4" x-show="step === 1">
                <label class="block text-sm">Résumé professionnel
                    <textarea rows="8" class="mt-1 w-full rounded-xl border-slate-300" x-model="content.summary"></textarea>
                </label>
            </div>

            <div class="mt-4 space-y-4" x-show="step === 2">
                <template x-for="(item, index) in content.experiences" :key="item.id">
                    <div class="rounded-xl border border-slate-200 p-3">
                        <input class="w-full rounded-lg border-slate-300" placeholder="Poste" x-model="item.title">
                        <input class="mt-2 w-full rounded-lg border-slate-300" placeholder="Organisation" x-model="item.company">
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <input class="rounded-lg border-slate-300" placeholder="Début" x-model="item.start">
                            <input class="rounded-lg border-slate-300" placeholder="Fin" x-model="item.end">
                        </div>
                        <label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" x-model="item.current"> Poste actuel</label>
                        <textarea class="mt-2 w-full rounded-lg border-slate-300" rows="3" placeholder="Ce que vous avez fait, sans ajouter de résultat non réel" x-model="item.description"></textarea>
                        <div class="mt-2 flex gap-2 text-sm">
                            <button type="button" @click="move('experiences', index, -1)">Monter</button>
                            <button type="button" @click="move('experiences', index, 1)">Descendre</button>
                            <button type="button" class="text-red-600" @click="remove('experiences', index)">Retirer</button>
                        </div>
                    </div>
                </template>
                <button type="button" class="rounded-full bg-mist px-3 py-2 text-sm" @click="add('experiences', {title:'',company:'',location:'',start:'',end:'',current:false,description:''})">Ajouter une expérience</button>
            </div>

            <div class="mt-4 space-y-4" x-show="step === 3">
                <template x-for="(item, index) in content.education" :key="item.id">
                    <div class="rounded-xl border border-slate-200 p-3">
                        <input class="w-full rounded-lg border-slate-300" placeholder="Établissement" x-model="item.school">
                        <input class="mt-2 w-full rounded-lg border-slate-300" placeholder="Diplôme" x-model="item.degree">
                        <input class="mt-2 w-full rounded-lg border-slate-300" placeholder="Domaine" x-model="item.field">
                        <textarea class="mt-2 w-full rounded-lg border-slate-300" rows="2" x-model="item.description"></textarea>
                        <button type="button" class="mt-2 text-sm text-red-600" @click="remove('education', index)">Retirer</button>
                    </div>
                </template>
                <button type="button" class="rounded-full bg-mist px-3 py-2 text-sm" @click="add('education', {school:'',degree:'',field:'',start:'',end:'',description:''})">Ajouter une formation</button>
            </div>

            <div class="mt-4 space-y-3" x-show="step === 4">
                <template x-for="(item, index) in content.skills" :key="item.id">
                    <div class="flex gap-2">
                        <input class="w-full rounded-lg border-slate-300" placeholder="Compétence" x-model="item.name">
                        <input class="w-32 rounded-lg border-slate-300" placeholder="Niveau" x-model="item.level">
                        <button type="button" @click="remove('skills', index)">×</button>
                    </div>
                </template>
                <button type="button" class="rounded-full bg-mist px-3 py-2 text-sm" @click="add('skills', {name:'',level:''})">Ajouter</button>
            </div>
            <div class="mt-4 space-y-3" x-show="step === 5">
                <template x-for="(item, index) in content.languages" :key="item.id">
                    <div class="flex gap-2">
                        <input class="w-full rounded-lg border-slate-300" placeholder="Langue" x-model="item.name">
                        <input class="w-32 rounded-lg border-slate-300" placeholder="Niveau" x-model="item.level">
                        <button type="button" @click="remove('languages', index)">×</button>
                    </div>
                </template>
                <button type="button" class="rounded-full bg-mist px-3 py-2 text-sm" @click="add('languages', {name:'',level:''})">Ajouter</button>
            </div>
            <div class="mt-4 space-y-3" x-show="step === 6">
                <template x-for="(item, index) in content.certifications" :key="item.id">
                    <div class="grid gap-2 sm:grid-cols-3">
                        <input class="rounded-lg border-slate-300" placeholder="Intitulé" x-model="item.name">
                        <input class="rounded-lg border-slate-300" placeholder="Organisme" x-model="item.issuer">
                        <input class="rounded-lg border-slate-300" placeholder="Date" x-model="item.date">
                        <button type="button" class="text-left text-sm text-red-600" @click="remove('certifications', index)">Retirer</button>
                    </div>
                </template>
                <button type="button" class="rounded-full bg-mist px-3 py-2 text-sm" @click="add('certifications', {name:'',issuer:'',date:''})">Ajouter</button>
            </div>
            <div class="mt-4 space-y-3" x-show="step === 7">
                <template x-for="(item, index) in content.projects" :key="item.id">
                    <div class="rounded-xl border border-slate-200 p-3">
                        <input class="w-full rounded-lg border-slate-300" placeholder="Projet" x-model="item.name">
                        <input class="mt-2 w-full rounded-lg border-slate-300" placeholder="Lien" x-model="item.url">
                        <textarea class="mt-2 w-full rounded-lg border-slate-300" rows="2" x-model="item.description"></textarea>
                        <button type="button" class="text-sm text-red-600" @click="remove('projects', index)">Retirer</button>
                    </div>
                </template>
                <button type="button" class="rounded-full bg-mist px-3 py-2 text-sm" @click="add('projects', {name:'',url:'',description:''})">Ajouter</button>
            </div>
            <div class="mt-4" x-show="step === 8">
                <label class="block text-sm">Centres d’intérêt, séparés par des virgules
                    <input class="mt-1 w-full rounded-xl border-slate-300" :value="content.interests.join(', ')" @input="content.interests = $event.target.value.split(',').map(v => v.trim()).filter(Boolean)">
                </label>
            </div>
            <div class="mt-4 space-y-3" x-show="step === 9">
                <template x-for="(item, index) in content.references" :key="item.id">
                    <div class="grid gap-2">
                        <input class="rounded-lg border-slate-300" placeholder="Nom" x-model="item.name">
                        <input class="rounded-lg border-slate-300" placeholder="Fonction" x-model="item.role">
                        <input class="rounded-lg border-slate-300" placeholder="Contact" x-model="item.contact">
                        <button type="button" class="text-left text-sm text-red-600" @click="remove('references', index)">Retirer</button>
                    </div>
                </template>
                <button type="button" class="rounded-full bg-mist px-3 py-2 text-sm" @click="add('references', {name:'',role:'',contact:''})">Ajouter</button>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-2">
                <label class="text-sm">Modèle
                    <select class="mt-1 w-full rounded-xl border-slate-300" x-model="template_slug">
                        @foreach ($templates as $template)
                            <option value="{{ $template->slug }}" @disabled(! ($allowed[$template->slug] ?? false))>{{ $template->name }}{{ $template->is_premium ? ' · selon l’offre' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm">Couleur
                    <input type="color" class="mt-1 h-10 w-full" x-model="accent_color">
                </label>
            </div>
            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <button type="button" class="rounded-full bg-brand px-4 py-2 font-semibold text-white" @click="save(true)">Enregistrer une version</button>
                <a class="rounded-full border border-slate-300 px-4 py-2" href="{{ route('resumes.versions', $resume) }}">Historique</a>
                <a class="rounded-full border border-slate-300 px-4 py-2" href="{{ route('resumes.export.pdf', $resume) }}">PDF</a>
                <a class="rounded-full border border-slate-300 px-4 py-2" href="{{ route('resumes.export.docx', $resume) }}">Word</a>
            </div>
            @if ($errors->any())
                <ul class="mt-3 text-sm text-red-600">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            @endif
        </section>
        <aside class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <p class="border-b border-slate-100 px-4 py-3 text-sm font-medium">Aperçu</p>
            <iframe x-ref="preview" title="Aperçu du CV" class="h-[720px] w-full" :src="preview"></iframe>
        </aside>
    </div>
</x-app-layout>
