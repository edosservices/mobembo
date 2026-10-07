<div class="row g-2 g-md-3 withdrawal-steps" aria-label="Étapes du retrait">
    @foreach (\App\Support\WithdrawalProgress::steps($withdrawal ?? null) as $index => $step)
        <div class="col-12 col-md-4">
            <article @class(['withdrawal-step', 'is-'.$step['state']])>
                <span class="step-icon" aria-hidden="true">
                    @if ($step['state'] === 'refused')
                        <i class="bi bi-x-circle-fill"></i>
                    @elseif ($step['state'] === 'current')
                        <i class="bi bi-hourglass-split"></i>
                    @elseif ($step['state'] === 'done')
                        <i class="bi bi-check-circle-fill"></i>
                    @else
                        <i class="bi bi-circle"></i>
                    @endif
                </span>
                <strong>Étape {{ $index + 1 }}</strong>
                <p>{{ $step['title'] }}</p>
                <span>{{ $step['detail'] }}</span>
            </article>
        </div>
    @endforeach
</div>
