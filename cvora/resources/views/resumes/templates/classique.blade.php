<div style="text-align: center; padding-bottom: 8px;">
    <h1 style="font-size: 24px; letter-spacing: 1px;">{{ $name }}</h1>
    <p>{{ $personal['title'] }}</p>
    <p class="muted">{{ implode(' · ', array_filter([$personal['email'], $personal['phone'], $personal['city'], $personal['country']])) }}</p>
</div>
<hr class="rule">
@if ($content['summary'] !== '')
    <h2 style="text-align: center; font-size: 12px; letter-spacing: 2px;">PROFIL</h2>
    <p class="block">{{ $content['summary'] }}</p>
@endif
@foreach (['experiences' => 'EXPÉRIENCE', 'education' => 'FORMATION'] as $key => $label)
    @if ($content[$key] !== [])
        <h2 style="text-align: center; font-size: 12px; letter-spacing: 2px;">{{ $label }}</h2>
        <hr class="rule">
        @foreach ($content[$key] as $item)
            <div class="block">
                <p><strong>{{ $item['title'] ?? $item['degree'] ?? '' }}</strong> — {{ $item['company'] ?? $item['school'] ?? '' }}</p>
                <p class="muted">{{ $period($item) }}</p>
                <p>{{ $item['description'] ?? '' }}</p>
            </div>
        @endforeach
    @endif
@endforeach
@if ($content['skills'] !== [])
    <h2 style="text-align: center; font-size: 12px;">COMPÉTENCES</h2>
    <p>{{ collect($content['skills'])->pluck('name')->implode(' · ') }}</p>
@endif
