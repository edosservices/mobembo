<?php

namespace App\Services\Resumes;

use App\Models\Resume;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocxExporter
{
    public function download(Resume $resume): BinaryFileResponse
    {
        $resume->loadMissing('template');
        $content = ResumeContent::normalize($resume->content ?? []);
        $slug = $resume->template?->slug ?? 'moderne';
        $accent = ltrim(ResumeContent::accent($resume->accent_color), '#');
        $document = new PhpWord;
        $document->setDefaultFontName('Calibri');
        $document->setDefaultFontSize(11);
        $section = $document->addSection([
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 900,
            'marginRight' => 900,
        ]);

        $personal = $content['personal'];
        $name = ResumeContent::fullName($content);
        $section->addText($name, ['bold' => true, 'size' => 22, 'color' => $accent], ['alignment' => Jc::CENTER]);

        if ($personal['title'] !== '') {
            $section->addText($personal['title'], ['size' => 12, 'color' => '1F2937'], ['alignment' => Jc::CENTER]);
        }

        $contact = array_filter([
            $personal['email'],
            $personal['phone'],
            trim($personal['city'].' '.$personal['country']),
            $personal['linkedin'],
            $personal['website'],
        ]);
        $section->addText(implode(' · ', $contact), ['size' => 9, 'color' => '4B5563'], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

        $order = match ($slug) {
            'etudiant' => ['summary', 'education', 'projects', 'experiences', 'skills', 'languages', 'certifications', 'interests'],
            'technique' => ['summary', 'skills', 'projects', 'experiences', 'education', 'certifications', 'languages'],
            'experimente' => ['summary', 'experiences', 'education', 'skills', 'certifications', 'languages'],
            default => ['summary', 'experiences', 'education', 'skills', 'languages', 'certifications', 'projects', 'interests', 'references'],
        };

        foreach ($order as $sectionName) {
            $this->writeSection($section, $sectionName, $content, $accent);
        }

        $path = tempnam(sys_get_temp_dir(), 'cvora');
        IOFactory::createWriter($document, 'Word2007')->save($path);
        $filename = (Str::slug($resume->title) ?: 'cv').'.docx';

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function writeSection(Section $section, string $name, array $content, string $accent): void
    {
        $title = match ($name) {
            'summary' => 'Profil',
            'experiences' => 'Expérience',
            'education' => 'Formation',
            'skills' => 'Compétences',
            'languages' => 'Langues',
            'certifications' => 'Certifications',
            'projects' => 'Projets',
            'interests' => 'Centres d’intérêt',
            'references' => 'Références',
            default => '',
        };

        if ($name === 'summary' && $content['summary'] !== '') {
            $section->addText($title, ['bold' => true, 'size' => 13, 'color' => $accent], ['spaceBefore' => 200]);
            $section->addText($content['summary']);

            return;
        }

        if ($name === 'interests' && $content['interests'] !== []) {
            $section->addText($title, ['bold' => true, 'size' => 13, 'color' => $accent], ['spaceBefore' => 200]);
            $section->addText(implode(', ', $content['interests']));

            return;
        }

        $rows = $content[$name] ?? [];

        if (! is_array($rows) || $rows === []) {
            return;
        }

        $section->addText($title, ['bold' => true, 'size' => 13, 'color' => $accent], ['spaceBefore' => 200]);

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $heading = match ($name) {
                'experiences' => trim(($row['title'] ?? '').(($row['company'] ?? '') !== '' ? ' — '.$row['company'] : '')),
                'education' => trim(($row['degree'] ?? '').(($row['school'] ?? '') !== '' ? ' — '.$row['school'] : '')),
                'skills', 'languages', 'certifications' => trim((string) ($row['name'] ?? '')),
                'projects' => (string) ($row['name'] ?? ''),
                'references' => trim(($row['name'] ?? '').(($row['role'] ?? '') !== '' ? ' — '.$row['role'] : '')),
                default => '',
            };

            if ($heading !== '') {
                $section->addText($heading, ['bold' => true, 'size' => 11]);
            }

            $meta = trim(implode(' · ', array_filter([
                $row['location'] ?? null,
                $row['start'] ?? null,
                ($row['current'] ?? false) ? 'En cours' : ($row['end'] ?? null),
                $row['field'] ?? null,
                $row['level'] ?? null,
                $row['issuer'] ?? null,
                $row['date'] ?? null,
                $row['contact'] ?? null,
                $row['url'] ?? null,
            ])));

            if ($meta !== '') {
                $section->addText($meta, ['italic' => true, 'size' => 9, 'color' => '4B5563']);
            }

            if (! empty($row['description'])) {
                $section->addText((string) $row['description']);
            }
        }
    }
}
