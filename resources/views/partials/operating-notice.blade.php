@if (\App\Support\BusinessCalendar::isMaintenance())
    <p class="alert alert-warning">Samedi : jour de maintenance. Les revenus estimatifs restent stables et reprennent lundi.</p>
@elseif (! \App\Support\BusinessCalendar::growsOn(now()) && ! \App\Support\BusinessCalendar::withdrawalsOpen())
    <p class="alert alert-warning">Dimanche : les revenus estimatifs et le traitement des retraits reprennent lundi.</p>
@endif
