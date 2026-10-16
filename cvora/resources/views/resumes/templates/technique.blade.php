<p class="label" style="color: {{ $accent }};">Profil technique</p>
<h1 style="font-size: 22px;">{{ $name }}</h1>
<p>{{ $personal['title'] }} · {{ $personal['email'] }} · {{ $personal['city'] }}</p>
@if ($content['skills'] !== [])
    <table width="100%" cellspacing="4" cellpadding="0" style="margin-top: 10px;">
        <tr>
            @foreach ($content['skills'] as $skill)
                <td style="border: 1px solid {{ $accent }}; padding: 4px 6px;">{{ $skill['name'] }}</td>
            @endforeach
        </tr>
    </table>
@endif
@if ($content['projects'] !== [])
    <h2 style="margin-top: 12px; color: {{ $accent }};">Projets</h2>
    @foreach ($content['projects'] as $item)
        <div class="block"><p><strong>{{ $item['name'] }}</strong> {{ $item['url'] }}</p><p>{{ $item['description'] }}</p></div>
    @endforeach
@endif
<h2 style="color: {{ $accent }};">Expérience</h2>
@foreach ($content['experiences'] as $item)
    <div class="block"><p><strong>{{ $item['title'] }}</strong> — {{ $item['company'] }} ({{ $period($item) }})</p><p>{{ $item['description'] }}</p></div>
@endforeach
@foreach ($content['education'] as $item)
    <p class="muted">{{ $item['degree'] }} — {{ $item['school'] }}</p>
@endforeach
