<table width="100%" cellspacing="0" cellpadding="0">
    <tr>
        <td class="side" style="background: {{ $accent }};">
            <h1 style="font-size: 20px;">{{ $name }}</h1>
            <p style="margin-top: 4px;">{{ $personal['title'] }}</p>
            <p class="label" style="margin-top: 16px;">Contact</p>
            <p>{{ $personal['email'] }}</p>
            <p>{{ $personal['phone'] }}</p>
            <p>{{ trim($personal['city'].' '.$personal['country']) }}</p>
            <p>{{ $personal['linkedin'] }}</p>
            @if ($content['skills'] !== [])
                <p class="label" style="margin-top: 16px;">Compétences</p>
                @foreach ($content['skills'] as $skill)
                    <p>{{ $skill['name'] }}</p>
                @endforeach
            @endif
            @if ($content['languages'] !== [])
                <p class="label" style="margin-top: 16px;">Langues</p>
                @foreach ($content['languages'] as $language)
                    <p>{{ $language['name'] }} {{ $language['level'] }}</p>
                @endforeach
            @endif
        </td>
        <td class="main">
            @if ($content['summary'] !== '')
                <h2 style="color: {{ $accent }};">Profil</h2>
                <p class="block">{{ $content['summary'] }}</p>
            @endif
            @if ($content['experiences'] !== [])
                <h2 style="color: {{ $accent }};">Expérience</h2>
                @foreach ($content['experiences'] as $item)
                    <div class="block">
                        <p><strong>{{ $item['title'] }}</strong> — {{ $item['company'] }}</p>
                        <p class="muted">{{ $period($item) }} {{ $item['location'] }}</p>
                        <p>{{ $item['description'] }}</p>
                    </div>
                @endforeach
            @endif
            @if ($content['education'] !== [])
                <h2 style="color: {{ $accent }};">Formation</h2>
                @foreach ($content['education'] as $item)
                    <div class="block">
                        <p><strong>{{ $item['degree'] }}</strong> — {{ $item['school'] }}</p>
                        <p class="muted">{{ $item['field'] }} {{ $period($item) }}</p>
                    </div>
                @endforeach
            @endif
        </td>
    </tr>
</table>
