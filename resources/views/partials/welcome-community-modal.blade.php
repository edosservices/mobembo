@php($welcome = \App\Models\PlatformSetting::current())
@if (filled($welcome->whatsapp_url) || filled($welcome->telegram_url))
    <div class="modal fade" id="welcome-community" tabindex="-1" aria-labelledby="welcome-community-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <p class="kicker mb-0">ZELVORA</p>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body pt-2">
                    <h2 class="modal-title" id="welcome-community-title">Bienvenue sur ZELVORA</h2>
                    <p>Rejoignez notre communauté WhatsApp et Telegram pour recevoir nos actualités, opportunités et annonces.</p>
                    @include('partials.community-links', ['variant' => 'buttons'])
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button class="btn btn-dark rounded-pill px-4" type="button" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endif
