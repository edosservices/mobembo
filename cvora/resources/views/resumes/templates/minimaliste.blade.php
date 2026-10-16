<h1 style="font-size: 28px; font-weight: normal;">{{ $name }}</h1>
<p class="muted" style="margin: 4px 0 14px;">{{ $personal['title'] }} — {{ implode(', ', array_filter([$personal['city'], $personal['email']])) }}</p>
@if ($content['summary'] !== '')
    <p class="block" style="font-size: 12px;">{{ $content['summary'] }}</p>
@endif
@foreach ($content['experiences'] as $item)
    <div class="block" style="margin-top: 12px;">
        <p>{{ $item['title'] }}</p>
        <p class="muted">{{ $item['company'] }} · {{ $period($item) }}</p>
        <p>{{ $item['description'] }}</p>
    </div>
@endforeach
@if ($content['skills'] !== [])
    <p style="margin-top: 16px;">{{ collect($content['skills'])->pluck('name')->implode(', ') }}</p>
@endif
@foreach ($content['education'] as $item)
    <p class="muted" style="margin-top: 8px;">{{ $item['degree'] }}, {{ $item['school'] }}</p>
@endforeach
