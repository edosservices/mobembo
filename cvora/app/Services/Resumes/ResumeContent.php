<?php

namespace App\Services\Resumes;

use App\Models\User;

final class ResumeContent
{
    /**
     * @return array<string, mixed>
     */
    public static function blank(): array
    {
        return [
            'personal' => [
                'first_name' => '',
                'last_name' => '',
                'title' => '',
                'email' => '',
                'phone' => '',
                'city' => '',
                'country' => '',
                'address' => '',
                'linkedin' => '',
                'website' => '',
            ],
            'summary' => '',
            'experiences' => [],
            'education' => [],
            'skills' => [],
            'languages' => [],
            'certifications' => [],
            'projects' => [],
            'interests' => [],
            'references' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromProfile(User $user): array
    {
        $content = self::blank();
        $profile = $user->profile;
        $parts = preg_split('/\s+/', trim($user->name), 2) ?: [];

        $content['personal']['first_name'] = $parts[0] ?? '';
        $content['personal']['last_name'] = $parts[1] ?? '';
        $content['personal']['email'] = $user->email;
        $content['personal']['phone'] = $profile?->phone ?: ($user->phone ?? '');
        $content['personal']['title'] = $profile?->headline ?? '';
        $content['personal']['city'] = $profile?->city ?? '';
        $content['personal']['country'] = $profile?->country ?? '';
        $content['personal']['address'] = $profile?->address ?? '';
        $content['personal']['linkedin'] = $profile?->linkedin ?? '';
        $content['personal']['website'] = $profile?->website ?? '';
        $content['summary'] = $profile?->summary ?? '';

        return $content;
    }

    /**
     * Données factices, uniquement pour illustrer un modèle.
     *
     * @return array<string, mixed>
     */
    public static function sample(): array
    {
        $content = self::blank();
        $content['personal'] = [
            'first_name' => 'Prénom',
            'last_name' => 'Nom',
            'title' => 'Poste visé',
            'email' => 'prenom.nom@example.com',
            'phone' => '+243 000 000 000',
            'city' => 'Kinshasa',
            'country' => 'République démocratique du Congo',
            'address' => '',
            'linkedin' => '',
            'website' => '',
        ];
        $content['summary'] = 'Résumé rédigé par le candidat. Cet aperçu montre seulement la mise en page du modèle.';
        $content['experiences'] = [[
            'id' => 'sample-exp',
            'title' => 'Intitulé du poste',
            'company' => 'Organisation',
            'location' => 'Kinshasa',
            'start' => '2022',
            'end' => '2024',
            'current' => false,
            'description' => 'Réalisation décrite par le candidat, sans ajout extérieur.',
        ]];
        $content['education'] = [[
            'id' => 'sample-edu',
            'school' => 'Établissement',
            'degree' => 'Diplôme',
            'field' => 'Domaine',
            'start' => '2018',
            'end' => '2021',
            'description' => '',
        ]];
        $content['skills'] = [
            ['id' => 'sample-skill-1', 'name' => 'Compétence', 'level' => ''],
            ['id' => 'sample-skill-2', 'name' => 'Outil', 'level' => ''],
        ];
        $content['languages'] = [
            ['id' => 'sample-lang', 'name' => 'Français', 'level' => 'Courant'],
        ];

        return $content;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $blank = self::blank();
        $personal = is_array($input['personal'] ?? null) ? $input['personal'] : [];

        foreach (array_keys($blank['personal']) as $key) {
            $blank['personal'][$key] = self::text($personal[$key] ?? '', 160);
        }

        $blank['summary'] = self::text($input['summary'] ?? '', 4000, true);
        $blank['experiences'] = self::rows($input['experiences'] ?? [], ['title', 'company', 'location', 'start', 'end', 'description'], ['current']);
        $blank['education'] = self::rows($input['education'] ?? [], ['school', 'degree', 'field', 'start', 'end', 'description']);
        $blank['skills'] = self::rows($input['skills'] ?? [], ['name', 'level']);
        $blank['languages'] = self::rows($input['languages'] ?? [], ['name', 'level']);
        $blank['certifications'] = self::rows($input['certifications'] ?? [], ['name', 'issuer', 'date']);
        $blank['projects'] = self::rows($input['projects'] ?? [], ['name', 'url', 'description']);
        $blank['references'] = self::rows($input['references'] ?? [], ['name', 'role', 'contact']);
        $blank['interests'] = self::interests($input['interests'] ?? []);

        return $blank;
    }

    public static function accent(?string $color): string
    {
        if (is_string($color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1) {
            return strtoupper($color);
        }

        return '#2563EB';
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'content' => ['required', 'array'],
            'content.personal' => ['required', 'array'],
            'content.personal.first_name' => ['nullable', 'string', 'max:80'],
            'content.personal.last_name' => ['nullable', 'string', 'max:80'],
            'content.personal.title' => ['nullable', 'string', 'max:160'],
            'content.personal.email' => ['nullable', 'string', 'max:160'],
            'content.personal.phone' => ['nullable', 'string', 'max:40'],
            'content.personal.city' => ['nullable', 'string', 'max:80'],
            'content.personal.country' => ['nullable', 'string', 'max:80'],
            'content.personal.address' => ['nullable', 'string', 'max:160'],
            'content.personal.linkedin' => ['nullable', 'string', 'max:160'],
            'content.personal.website' => ['nullable', 'string', 'max:160'],
            'content.summary' => ['nullable', 'string', 'max:4000'],
            'content.experiences' => ['array', 'max:40'],
            'content.experiences.*.title' => ['nullable', 'string', 'max:160'],
            'content.experiences.*.company' => ['nullable', 'string', 'max:160'],
            'content.experiences.*.location' => ['nullable', 'string', 'max:120'],
            'content.experiences.*.start' => ['nullable', 'string', 'max:20'],
            'content.experiences.*.end' => ['nullable', 'string', 'max:20'],
            'content.experiences.*.current' => ['nullable', 'boolean'],
            'content.experiences.*.description' => ['nullable', 'string', 'max:2000'],
            'content.education' => ['array', 'max:30'],
            'content.education.*.school' => ['nullable', 'string', 'max:160'],
            'content.education.*.degree' => ['nullable', 'string', 'max:160'],
            'content.education.*.field' => ['nullable', 'string', 'max:160'],
            'content.education.*.start' => ['nullable', 'string', 'max:20'],
            'content.education.*.end' => ['nullable', 'string', 'max:20'],
            'content.education.*.description' => ['nullable', 'string', 'max:1000'],
            'content.skills' => ['array', 'max:40'],
            'content.skills.*.name' => ['nullable', 'string', 'max:80'],
            'content.skills.*.level' => ['nullable', 'string', 'max:40'],
            'content.languages' => ['array', 'max:20'],
            'content.languages.*.name' => ['nullable', 'string', 'max:40'],
            'content.languages.*.level' => ['nullable', 'string', 'max:40'],
            'content.certifications' => ['array', 'max:20'],
            'content.certifications.*.name' => ['nullable', 'string', 'max:160'],
            'content.certifications.*.issuer' => ['nullable', 'string', 'max:160'],
            'content.certifications.*.date' => ['nullable', 'string', 'max:20'],
            'content.projects' => ['array', 'max:20'],
            'content.projects.*.name' => ['nullable', 'string', 'max:160'],
            'content.projects.*.url' => ['nullable', 'string', 'max:200'],
            'content.projects.*.description' => ['nullable', 'string', 'max:1000'],
            'content.interests' => ['array', 'max:20'],
            'content.interests.*' => ['nullable', 'string', 'max:80'],
            'content.references' => ['array', 'max:10'],
            'content.references.*.name' => ['nullable', 'string', 'max:120'],
            'content.references.*.role' => ['nullable', 'string', 'max:120'],
            'content.references.*.contact' => ['nullable', 'string', 'max:160'],
        ];
    }

    public static function fullName(array $content): string
    {
        $personal = $content['personal'] ?? [];
        $name = trim(($personal['first_name'] ?? '').' '.($personal['last_name'] ?? ''));

        return $name !== '' ? $name : 'CV sans nom';
    }

    /**
     * @param  list<string>  $keys
     * @param  list<string>  $flags
     * @return list<array<string, mixed>>
     */
    private static function rows(mixed $rows, array $keys, array $flags = []): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $clean = [];

        foreach (array_slice($rows, 0, 40) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $item = ['id' => self::identifier($row['id'] ?? null)];

            foreach ($keys as $key) {
                $item[$key] = self::text($row[$key] ?? '', 2000, $key === 'description');
            }

            foreach ($flags as $flag) {
                $item[$flag] = filter_var($row[$flag] ?? false, FILTER_VALIDATE_BOOLEAN);
            }

            $hasText = false;

            foreach ($keys as $key) {
                if ($item[$key] !== '') {
                    $hasText = true;
                }
            }

            if ($hasText) {
                $clean[] = $item;
            }
        }

        return $clean;
    }

    /**
     * @return list<string>
     */
    private static function interests(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $clean = [];

        foreach (array_slice($values, 0, 20) as $value) {
            $text = self::text($value, 80);

            if ($text !== '') {
                $clean[] = $text;
            }
        }

        return $clean;
    }

    private static function text(mixed $value, int $limit, bool $multiline = false): string
    {
        $raw = strip_tags((string) $value);

        if ($multiline) {
            $raw = str_replace(["\r\n", "\r"], "\n", $raw);
            $raw = preg_replace("/[ \t]+/u", ' ', $raw) ?? $raw;
            $raw = preg_replace("/\n{3,}/u", "\n\n", $raw) ?? $raw;
        } else {
            $raw = preg_replace('/\s+/u', ' ', $raw) ?? '';
        }

        return mb_substr(trim($raw), 0, $limit);
    }

    private static function identifier(mixed $value): string
    {
        $id = is_string($value) ? preg_replace('/[^A-Za-z0-9\-]/', '', $value) : null;

        if (! is_string($id) || strlen($id) < 4) {
            return bin2hex(random_bytes(8));
        }

        return substr($id, 0, 40);
    }
}
