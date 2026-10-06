@php($footerCommunity = \App\Models\PlatformSetting::current())
<footer class="site-footer">
    <div class="container py-5">
        <div class="row g-4 text-center text-lg-start">
            <div class="col-12 col-md-6 col-lg-3">
                <img class="footer-logo mx-auto mx-lg-0" src="{{ asset('images/logo.png') }}" alt="ZELVORA">
                <p class="footer-tagline mx-auto mx-lg-0">Votre argent travaille pour vous pendant que vous dormez.</p>
            </div>
            <div class="col-6 col-lg-3">
                <h2 class="footer-heading">Navigation</h2>
                <nav class="footer-links" aria-label="Navigation">
                    <a href="{{ route('login') }}">Connexion</a>
                    <a href="{{ route('projects.index') }}">Opportunités</a>
                    <a href="{{ route('investments.index') }}">Investissements</a>
                    <a href="{{ route('about') }}">À propos</a>
                    <a href="{{ route('contact') }}">Contact</a>
                </nav>
            </div>
            <div class="col-6 col-lg-3">
                <h2 class="footer-heading">Communauté</h2>
                @include('partials.community-links')
                @unless (filled($footerCommunity->whatsapp_url) || filled($footerCommunity->telegram_url))
                    <p class="mb-0">Les groupes seront indiqués ici.</p>
                @endunless
            </div>
            <div class="col-12 col-lg-3">
                <h2 class="footer-heading">Informations</h2>
                <nav class="footer-links" aria-label="Informations">
                    <a href="{{ route('terms') }}">Conditions</a>
                    <a href="{{ route('privacy') }}">Politique de confidentialité</a>
                    <a href="{{ route('legal') }}">Mentions légales</a>
                    <a href="{{ route('contact') }}">Contact</a>
                </nav>
            </div>
        </div>
        <p class="footer-copy text-center mb-0 mt-4">© {{ date('Y') }} ZELVORA. Tous droits réservés.</p>
    </div>
</footer>
