<?php

namespace App\Services\Resumes;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\Element\AbstractElement;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;
use Throwable;

class ResumeImporter
{
    /**
     * @return array{text: string, warning: ?string, content: array<string, mixed>}
     */
    public function inspect(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();

        if (! $this->mimeAllowed($extension, $mime)) {
            throw new \RuntimeException('Seuls les fichiers PDF et DOCX sont acceptés.');
        }

        try {
            $text = $extension === 'pdf'
                ? $this->pdfText((string) file_get_contents($file->getRealPath()))
                : $this->docxText($file->getRealPath() ?: $file->getPathname());
        } catch (Throwable) {
            throw new \RuntimeException('Le fichier n’a pas pu être lu. Il n’a pas été exécuté.');
        }

        $text = trim(mb_substr($text, 0, 200000));
        $warning = null;

        if (mb_strlen($text) < 30) {
            $warning = 'Ce PDF ne contient pas de texte exploitable. S’il s’agit d’un document scanné, la reconnaissance optique n’est pas encore branchée. Complétez les champs à la main.';
        }

        return [
            'text' => $text,
            'warning' => $warning,
            'content' => $this->detect($text),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detect(string $text): array
    {
        $content = ResumeContent::blank();
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $match) === 1) {
            $content['personal']['email'] = $match[0];
        }

        if (preg_match('/(?:\+|00)?\d[\d\s().-]{7,}\d/', $text, $match) === 1) {
            $content['personal']['phone'] = trim($match[0]);
        }

        if (preg_match('#https?://(?:[\w.-]+\.)?linkedin\.com/\S+#i', $text, $match) === 1) {
            $content['personal']['linkedin'] = rtrim($match[0], '.,)');
        }

        $sections = $this->sections($text);
        $content['summary'] = $sections['summary'] ?? '';
        $content['experiences'] = $this->blocks($sections['experiences'] ?? '', 'experience');
        $content['education'] = $this->blocks($sections['education'] ?? '', 'education');
        $content['skills'] = $this->namedList($sections['skills'] ?? '', 'skill');
        $content['languages'] = $this->namedList($sections['languages'] ?? '', 'language');
        $content['certifications'] = $this->namedList($sections['certifications'] ?? '', 'certification');
        $content['projects'] = $this->blocks($sections['projects'] ?? '', 'project');
        $content['interests'] = $this->interestList($sections['interests'] ?? '');

        $name = $this->guessName($sections['preamble'] ?? '');

        if ($name !== null) {
            $parts = preg_split('/\s+/', $name, 2) ?: [];
            $content['personal']['first_name'] = $parts[0] ?? '';
            $content['personal']['last_name'] = $parts[1] ?? '';
        }

        return ResumeContent::normalize($content);
    }

    private function mimeAllowed(string $extension, string $mime): bool
    {
        if ($extension === 'pdf') {
            return $mime === 'application/pdf';
        }

        if ($extension !== 'docx') {
            return false;
        }

        return in_array($mime, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
        ], true);
    }

    private function pdfText(string $binary): string
    {
        return (new Parser)->parseContent($binary)->getText();
    }

    private function docxText(string $path): string
    {
        $document = IOFactory::load($path);
        $chunks = [];

        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $this->collectText($element, $chunks);
            }
        }

        return implode("\n", $chunks);
    }

    /**
     * @param  list<string>  $chunks
     */
    private function collectText(mixed $element, array &$chunks): void
    {
        if (! $element instanceof AbstractElement) {
            return;
        }

        if (method_exists($element, 'getText')) {
            $text = $element->getText();

            if (is_string($text) && trim($text) !== '') {
                $chunks[] = trim($text);
            }
        }

        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $this->collectText($child, $chunks);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function sections(string $text): array
    {
        $map = [
            'profil' => 'summary',
            'profile' => 'summary',
            'résumé' => 'summary',
            'resume' => 'summary',
            'à propos' => 'summary',
            'a propos' => 'summary',
            'expérience' => 'experiences',
            'experience' => 'experiences',
            'expériences' => 'experiences',
            'expériences professionnelles' => 'experiences',
            'parcours professionnel' => 'experiences',
            'formation' => 'education',
            'formations' => 'education',
            'études' => 'education',
            'etudes' => 'education',
            'diplômes' => 'education',
            'diplomes' => 'education',
            'compétences' => 'skills',
            'competences' => 'skills',
            'langues' => 'languages',
            'certifications' => 'certifications',
            'projets' => 'projects',
            'centres d’intérêt' => 'interests',
            'centres d\'intérêt' => 'interests',
            'loisirs' => 'interests',
            'références' => 'references',
            'references' => 'references',
        ];

        $current = 'preamble';
        $buckets = ['preamble' => ''];

        foreach (preg_split('/\n/', $text) ?: [] as $line) {
            $label = mb_strtolower(trim($line, " \t\n\r\0\x0B:.-•"));

            if (isset($map[$label]) && mb_strlen($label) <= 40) {
                $current = $map[$label];
                $buckets[$current] ??= '';

                continue;
            }

            $buckets[$current] = trim(($buckets[$current] ?? '')."\n".$line);
        }

        return $buckets;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function blocks(string $text, string $kind): array
    {
        $parts = preg_split("/\n\s*\n/", trim($text)) ?: [];
        $rows = [];

        foreach ($parts as $part) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\n/', $part) ?: [])));

            if ($lines === []) {
                continue;
            }

            if ($kind === 'education') {
                $rows[] = [
                    'school' => $lines[0],
                    'degree' => $lines[1] ?? '',
                    'description' => implode("\n", array_slice($lines, 2)),
                ];
            } elseif ($kind === 'project') {
                $rows[] = [
                    'name' => $lines[0],
                    'description' => implode("\n", array_slice($lines, 1)),
                ];
            } else {
                $rows[] = [
                    'title' => $lines[0],
                    'company' => $lines[1] ?? '',
                    'description' => implode("\n", array_slice($lines, 2)),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, string>>
     */
    private function namedList(string $text, string $kind): array
    {
        $bits = preg_split('/[,;\n•]+/u', $text) ?: [];
        $rows = [];

        foreach ($bits as $bit) {
            $name = trim($bit);

            if ($name === '') {
                continue;
            }

            $rows[] = $kind === 'certification'
                ? ['name' => $name]
                : ['name' => $name, 'level' => ''];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function interestList(string $text): array
    {
        $bits = preg_split('/[,;\n•]+/u', $text) ?: [];

        return array_values(array_filter(array_map('trim', $bits)));
    }

    private function guessName(string $preamble): ?string
    {
        foreach (preg_split('/\n/', $preamble) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_contains($line, '@') || preg_match('/\d{3,}/', $line) === 1) {
                continue;
            }

            if (mb_strlen($line) <= 60 && preg_match('/^[\p{L}\s\'’-]+$/u', $line) === 1) {
                return $line;
            }
        }

        return null;
    }
}
