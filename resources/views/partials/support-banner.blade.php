@if (session('impersonator_id') && auth()->check())
    <div class="support-banner">
        <p>Dépannage du compte de {{ auth()->user()->name }}. Les dépôts, investissements et retraits restent réservés au client.</p>
        <form method="POST" action="{{ route('support.leave') }}">
            @csrf
            <button class="btn-z small" type="submit">Retour à l'administration</button>
        </form>
    </div>
@endif
