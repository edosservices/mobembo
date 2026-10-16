<table width="100%" cellspacing="0" cellpadding="6" style="border: 1px solid #1F2937;">
    <tr>
        <td style="width: 70%;"><h1 style="font-size: 20px;">{{ $name }}</h1><p>{{ $personal['title'] }}</p></td>
        <td>
            <p>{{ $personal['email'] }}</p>
            <p>{{ $personal['phone'] }}</p>
            <p>{{ $personal['address'] }} {{ $personal['city'] }}</p>
        </td>
    </tr>
</table>
@php $index = 1; @endphp
@if ($content['summary'] !== '')
    <h2 style="margin-top: 10px;">{{ $index++ }}. Profil</h2>
    <p class="block">{{ $content['summary'] }}</p>
@endif
@if ($content['experiences'] !== [])
    <h2>{{ $index++ }}. Expérience professionnelle</h2>
    @foreach ($content['experiences'] as $item)
        <div class="block" style="border-bottom: 1px solid #E5E7EB; padding-bottom: 4px;">
            <p><strong>{{ $item['title'] }}</strong>, {{ $item['company'] }}, {{ $item['location'] }}</p>
            <p class="muted">{{ $period($item) }}</p>
            <p>{{ $item['description'] }}</p>
        </div>
    @endforeach
@endif
@if ($content['education'] !== [])
    <h2>{{ $index++ }}. Formation</h2>
    @foreach ($content['education'] as $item)
        <p class="block">{{ $item['degree'] }} — {{ $item['school'] }} ({{ $period($item) }})</p>
    @endforeach
@endif
@if ($content['references'] !== [])
    <h2>{{ $index }}. Références</h2>
    @foreach ($content['references'] as $item)
        <p>{{ $item['name'] }}, {{ $item['role'] }}, {{ $item['contact'] }}</p>
    @endforeach
@endif
