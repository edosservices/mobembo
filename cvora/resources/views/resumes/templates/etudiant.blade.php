<h1 style="font-size: 22px; color: {{ $accent }};">{{ $name }}</h1>
<p>{{ $personal['title'] }} · {{ $personal['city'] }} · {{ $personal['email'] }} · {{ $personal['phone'] }}</p>
<h2 style="margin-top: 12px; color: {{ $accent }};">Formation</h2>
@foreach ($content['education'] as $item)
    <div class="block">
        <p><strong>{{ $item['degree'] }}</strong> — {{ $item['field'] }}</p>
        <p>{{ $item['school'] }} · {{ $period($item) }}</p>
        <p>{{ $item['description'] }}</p>
    </div>
@endforeach
@if ($content['projects'] !== [])
    <h2 style="color: {{ $accent }};">Projets</h2>
    @foreach ($content['projects'] as $item)
        <div class="block"><p><strong>{{ $item['name'] }}</strong></p><p>{{ $item['description'] }}</p></div>
    @endforeach
@endif
@if ($content['experiences'] !== [])
    <h2 style="color: {{ $accent }};">Expériences</h2>
    @foreach ($content['experiences'] as $item)
        <div class="block"><p><strong>{{ $item['title'] }}</strong>, {{ $item['company'] }}</p><p>{{ $item['description'] }}</p></div>
    @endforeach
@endif
@if ($content['skills'] !== [])
    <p style="margin-top: 8px;"><strong>Compétences :</strong> {{ collect($content['skills'])->pluck('name')->implode(', ') }}</p>
@endif
