<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <img class="footer-logo" src="{{ asset('images/logo.png') }}" alt="ZELVORA">
            <p class="footer-tagline">Investir dans l'immobilier, simplement.</p>
        </div>
        <nav class="footer-links" aria-label="Navigation">
            <a href="{{ route('home') }}">Accueil</a>
            <a href="{{ route('projects.index') }}">Projets</a>
            <a href="{{ route('home') }}#comment-ca-marche">Comment ça marche</a>
            <a href="{{ route('about') }}">À propos</a>
            <a href="{{ route('faq') }}">FAQ</a>
        </nav>
        <nav class="footer-links" aria-label="Informations">
            <a href="{{ route('contact') }}">Contact</a>
            <a href="{{ route('terms') }}">Conditions</a>
            <a href="{{ route('privacy') }}">Confidentialité</a>
        </nav>
    </div>
    <div class="wrap footer-copy">© ZELVORA</div>
</footer>
