<?php

namespace App\Services\Resumes;

use App\Exceptions\QuotaExceededException;
use App\Models\Resume;
use App\Models\ResumeTemplate;
use App\Models\ResumeVersion;
use App\Models\User;
use App\Services\Billing\QuotaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResumeWriter
{
    public function __construct(private readonly QuotaService $quotas) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data = []): Resume
    {
        try {
            $this->quotas->assert($user, 'create_resume');
        } catch (QuotaExceededException $exception) {
            throw ValidationException::withMessages(['title' => $exception->getMessage()]);
        }

        $template = $this->template($user, $data['template_slug'] ?? $this->defaultSlug($user));
        $content = ResumeContent::normalize($data['content'] ?? ResumeContent::fromProfile($user));

        return $user->resumes()->create([
            'template_id' => $template->id,
            'title' => $this->title($data['title'] ?? 'Mon CV'),
            'accent_color' => ResumeContent::accent($data['accent_color'] ?? '#2563EB'),
            'content' => $content,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(User $user, Resume $resume, array $data, bool $recordVersion, string $source = 'manual'): Resume
    {
        $template = $this->template($user, (string) ($data['template_slug'] ?? $resume->template?->slug ?? 'moderne'));
        $content = ResumeContent::normalize($data['content'] ?? []);

        DB::transaction(function () use ($user, $resume, $data, $recordVersion, $source, $template, $content): void {
            if ($recordVersion) {
                $this->snapshot($user, $resume, $source);
            }

            $resume->fill([
                'template_id' => $template->id,
                'title' => $this->title($data['title'] ?? $resume->title),
                'accent_color' => ResumeContent::accent($data['accent_color'] ?? $resume->accent_color),
                'content' => $content,
            ])->save();
        });

        return $resume->refresh();
    }

    public function duplicate(User $user, Resume $resume): Resume
    {
        try {
            $this->quotas->assert($user, 'create_resume');
        } catch (QuotaExceededException $exception) {
            throw ValidationException::withMessages(['title' => $exception->getMessage()]);
        }

        if (! $this->quotas->templateAllowedFor($user, $resume->template ?? $this->template($user, 'moderne'))) {
            throw ValidationException::withMessages([
                'template_slug' => 'Ce modèle n’est pas inclus dans votre offre actuelle.',
            ]);
        }

        return $user->resumes()->create([
            'template_id' => $resume->template_id,
            'title' => $this->title('Copie de '.$resume->title),
            'accent_color' => $resume->accent_color,
            'content' => ResumeContent::normalize($resume->content ?? []),
        ]);
    }

    public function restore(User $user, Resume $resume, ResumeVersion $version): Resume
    {
        $this->assertVersions($user);
        $snapshot = is_array($version->snapshot) ? $version->snapshot : [];

        return $this->save($user, $resume, [
            'title' => $snapshot['title'] ?? $resume->title,
            'template_slug' => $snapshot['template_slug'] ?? $resume->template?->slug,
            'accent_color' => $snapshot['accent_color'] ?? $resume->accent_color,
            'content' => $snapshot['content'] ?? [],
        ], true, 'restore');
    }

    public function snapshot(User $user, Resume $resume, string $source, ?string $label = null): void
    {
        $resume->loadMissing('template');

        ResumeVersion::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'label' => $label,
            'source' => $source,
            'snapshot' => [
                'title' => $resume->title,
                'template_slug' => $resume->template?->slug,
                'accent_color' => $resume->accent_color,
                'content' => $resume->content,
            ],
            'created_at' => now(),
        ]);
    }

    public function template(User $user, string $slug): ResumeTemplate
    {
        $template = ResumeTemplate::query()->where('slug', $slug)->where('is_active', true)->first();

        if ($template === null) {
            throw ValidationException::withMessages([
                'template_slug' => 'Ce modèle est introuvable.',
            ]);
        }

        if (! $this->quotas->templateAllowedFor($user, $template)) {
            throw ValidationException::withMessages([
                'template_slug' => 'Ce modèle n’est pas inclus dans votre offre actuelle.',
            ]);
        }

        return $template;
    }

    public function assertVersions(User $user): void
    {
        try {
            $this->quotas->assert($user, 'versions');
        } catch (QuotaExceededException $exception) {
            throw ValidationException::withMessages(['version' => $exception->getMessage()]);
        }
    }

    private function defaultSlug(User $user): string
    {
        $preferred = $user->profile?->preferences()['template'] ?? null;

        if (is_string($preferred) && $preferred !== '') {
            return $preferred;
        }

        return 'moderne';
    }

    private function title(mixed $title): string
    {
        $clean = trim(strip_tags((string) $title));

        return mb_substr($clean !== '' ? $clean : 'Mon CV', 0, 120);
    }
}
