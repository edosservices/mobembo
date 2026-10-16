<?php

namespace App\Services\Resumes;

use App\Models\Resume;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PdfExporter
{
    public function download(Resume $resume): Response
    {
        $resume->loadMissing('template');
        $content = ResumeContent::normalize($resume->content ?? []);
        $filename = $this->filename($resume, 'pdf');

        return Pdf::loadView('resumes.print', [
            'content' => $content,
            'template' => $resume->template?->slug ?? 'moderne',
            'accent' => ResumeContent::accent($resume->accent_color),
            'watermark' => null,
        ])->setPaper('a4')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', false)
            ->download($filename);
    }

    private function filename(Resume $resume, string $extension): string
    {
        $slug = Str::slug($resume->title);

        return ($slug !== '' ? $slug : 'cv').'.'.$extension;
    }
}
