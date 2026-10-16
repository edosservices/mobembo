<table width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td colspan="2" style="padding-bottom: 8px;">
            <h1 style="font-size: 22px;">{{ $name }}</h1>
            <p>{{ $personal['title'] }}</p>
        </td>
    </tr>
    @foreach ([
        'Contact' => implode(' | ', array_filter([$personal['email'], $personal['phone'], $personal['city'], $personal['country'], $personal['linkedin']])),
        'Profile' => $content['summary'],
    ] as $label => $value)
        @if ($value !== '')
            <tr>
                <td class="label" style="width: 22%; color: {{ $accent }}; vertical-align: top; padding: 4px 8px 4px 0;">{{ $label }}</td>
                <td style="padding: 4px 0;">{{ $value }}</td>
            </tr>
        @endif
    @endforeach
    @if ($content['experiences'] !== [])
        <tr><td class="label" style="color: {{ $accent }}; vertical-align: top;">Experience</td><td>
            @foreach ($content['experiences'] as $item)
                <div class="block"><p><strong>{{ $item['title'] }}</strong>, {{ $item['company'] }} <span class="muted">{{ $period($item) }}</span></p><p>{{ $item['description'] }}</p></div>
            @endforeach
        </td></tr>
    @endif
    @if ($content['education'] !== [])
        <tr><td class="label" style="color: {{ $accent }}; vertical-align: top;">Education</td><td>
            @foreach ($content['education'] as $item)
                <p class="block">{{ $item['degree'] }} — {{ $item['school'] }} <span class="muted">{{ $period($item) }}</span></p>
            @endforeach
        </td></tr>
    @endif
    @if ($content['skills'] !== [])
        <tr><td class="label" style="color: {{ $accent }};">Skills</td><td>{{ collect($content['skills'])->pluck('name')->implode(', ') }}</td></tr>
    @endif
    @if ($content['languages'] !== [])
        <tr><td class="label" style="color: {{ $accent }};">Languages</td><td>{{ collect($content['languages'])->map(fn ($language) => trim($language['name'].' '.$language['level']))->implode(', ') }}</td></tr>
    @endif
</table>
