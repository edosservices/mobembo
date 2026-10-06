<div class="row g-2 withdrawal-steps" aria-label="Étapes du retrait">
    @foreach (\App\Support\WithdrawalProgress::steps($withdrawal ?? null) as $index => $step)
        <div class="col-12 col-md-4">
            <article @class(['withdrawal-step', 'is-'.$step['state']])>
                <strong>Étape {{ $index + 1 }}</strong>
                <p>{{ $step['title'] }}</p>
                <span>{{ $step['detail'] }}</span>
            </article>
        </div>
    @endforeach
</div>
