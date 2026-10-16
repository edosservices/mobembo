<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1F2937; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p { margin: 0 0 12px; }
    </style>
</head>
<body>
    <h1>{{ $letter->title }}</h1>
    <p>{{ trim(($letter->company ?? '').($letter->job_title ? ' — '.$letter->job_title : '')) }}</p>
    @foreach (preg_split("/\n\s*\n/", trim($letter->content)) ?: [] as $paragraph)
        <p>{{ trim($paragraph) }}</p>
    @endforeach
</body>
</html>
