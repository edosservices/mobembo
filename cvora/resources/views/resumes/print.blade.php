<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ \App\Services\Resumes\ResumeContent::fullName($content) }}</title>
    <style>
        @page { margin: 18px 20px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #1F2937; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.45; }
        h1, h2, p { margin: 0; }
        .muted { color: #4B5563; }
        .block { page-break-inside: avoid; margin-bottom: 8px; }
        .watermark { margin-top: 12px; font-size: 9px; color: #6B7280; }
        .side { width: 32%; vertical-align: top; padding: 16px; color: #fff; }
        .main { vertical-align: top; padding: 16px 18px; }
        .rule { border: 0; border-top: 1px solid #D1D5DB; margin: 8px 0; }
        .label { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; }
    </style>
</head>
<body>
    @php
        $name = \App\Services\Resumes\ResumeContent::fullName($content);
        $personal = $content['personal'];
        $period = function (array $row): string {
            $end = ! empty($row['current']) ? 'Aujourd’hui' : ($row['end'] ?? '');
            return trim(($row['start'] ?? '').($end !== '' ? ' — '.$end : ''));
        };
    @endphp
    @includeIf('resumes.templates.'.$template, ['period' => $period])
    @if ($watermark)
        <p class="watermark">{{ $watermark }}. Les textes sont des emplacements, pas un parcours réel.</p>
    @endif
</body>
</html>
