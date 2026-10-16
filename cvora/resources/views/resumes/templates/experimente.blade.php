<table width="100%" cellspacing="0" cellpadding="0" style="background: {{ $accent }}; color: #fff; margin-bottom: 12px;">
    <tr><td style="padding: 16px 18px;">
        <h1 style="font-size: 24px;">{{ $name }}</h1>
        <p>{{ $personal['title'] }}</p>
        <p>{{ implode(' · ', array_filter([$personal['city'], $personal['email'], $personal['phone']])) }}</p>
    </td></tr>
</table>
@if ($content['summary'] !== '')
    <p class="block" style="font-size: 12px;">{{ $content['summary'] }}</p>
@endif
<table width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td style="width: 62%; vertical-align: top; padding-right: 12px;">
            <h2 style="color: {{ $accent }};">Parcours</h2>
            @foreach ($content['experiences'] as $item)
                <div class="block">
                    <p><strong>{{ $item['title'] }}</strong></p>
                    <p>{{ $item['company'] }} · {{ $period($item) }}</p>
                    <p>{{ $item['description'] }}</p>
                </div>
            @endforeach
        </td>
        <td style="width: 38%; vertical-align: top;">
            <h2 style="color: {{ $accent }};">Formation</h2>
            @foreach ($content['education'] as $item)
                <p class="block"><strong>{{ $item['degree'] }}</strong><br>{{ $item['school'] }}</p>
            @endforeach
            @if ($content['skills'] !== [])
                <h2 style="color: {{ $accent }};">Compétences</h2>
                <p>{{ collect($content['skills'])->pluck('name')->implode(', ') }}</p>
            @endif
        </td>
    </tr>
</table>
