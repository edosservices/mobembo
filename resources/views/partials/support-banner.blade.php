@if (session('impersonator_id') && auth()->check())
    <div class="support-banner">
        <p>Dépannage du compte de {{ auth()->user()->name }}. Les dépôts, investissements et retraits sont suspendus le temps de cette consultation.</p>
        <form method="POST" action="{{ route('support.leave') }}">
            @csrf
            <button class="btn-z small" type="submit">Revenir à l’administration</button>
        </form>
    </div>
@endif
