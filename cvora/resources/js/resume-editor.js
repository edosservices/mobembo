document.addEventListener('alpine:init', () => {
    window.Alpine.data('resumeEditor', () => ({
        title: '',
        template_slug: 'moderne',
        accent_color: '#2563EB',
        content: {},
        step: 0,
        status: '',
        preview: '',
        steps: ['Identité', 'Résumé', 'Expériences', 'Formation', 'Compétences', 'Langues', 'Certifications', 'Projets', 'Intérêts', 'Références'],
        init() {
            const state = JSON.parse(document.getElementById('resume-state').textContent);
            this.title = state.title;
            this.template_slug = state.template_slug;
            this.accent_color = state.accent_color;
            this.content = state.content;
            this.preview = state.preview;
            this.$watch('content', () => this.queue());
            this.$watch('title', () => this.queue());
            this.$watch('template_slug', () => this.queue());
            this.$watch('accent_color', () => this.queue());
        },
        queue() {
            clearTimeout(this._timer);
            this._timer = setTimeout(() => this.save(false), 900);
        },
        async save(version) {
            this.status = 'Enregistrement…';
            const response = await fetch(this.$root.dataset.saveUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    title: this.title,
                    template_slug: this.template_slug,
                    accent_color: this.accent_color,
                    record_version: version,
                    content: this.content,
                }),
            });
            if (!response.ok) {
                this.status = 'La sauvegarde a échoué. Vérifiez les champs.';
                return;
            }
            const payload = await response.json();
            this.status = 'Enregistré à ' + payload.saved_at;
            if (this.$refs.preview) {
                this.$refs.preview.src = this.preview + '?t=' + Date.now();
            }
        },
        add(list, item) {
            this.content[list].push({ id: crypto.randomUUID(), ...item });
        },
        remove(list, index) {
            this.content[list].splice(index, 1);
        },
        move(list, index, direction) {
            const next = index + direction;
            if (next < 0 || next >= this.content[list].length) {
                return;
            }
            const row = this.content[list].splice(index, 1)[0];
            this.content[list].splice(next, 0, row);
        },
    }));
});
