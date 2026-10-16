<?php

namespace App\Http\Controllers;

use App\Exceptions\AiNotConfiguredException;
use App\Exceptions\QuotaExceededException;
use App\Models\JobOffer;
use App\Models\Resume;
use App\Services\Ai\AiCareerService;
use App\Services\Billing\QuotaService;
use App\Services\Resumes\ResumeContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function index(Request $request, AiCareerService $ai, QuotaService $quotas): View
    {
        return view('assistant.index', [
            'configured' => $ai->configured(),
            'credits' => $quotas->remainingCredits($request->user()),
            'resumes' => $request->user()->resumes()->orderBy('title')->get(),
            'letters' => $request->user()->coverLetters()->latest()->limit(5)->get(),
        ]);
    }

    public function summary(Request $request, Resume $resume, AiCareerService $ai): JsonResponse
    {
        $this->authorize('view', $resume);
        $content = ResumeContent::normalize($resume->content ?? []);

        return $this->run($request, $ai, 'summary', 'Reformule le résumé professionnel en t’appuyant uniquement sur les faits ci-dessous. N’ajoute aucun emploi, diplôme ou compétence.', $this->pack($content));
    }

    public function experience(Request $request, Resume $resume, AiCareerService $ai): JsonResponse
    {
        $this->authorize('view', $resume);
        $data = $request->validate(['text' => ['required', 'string', 'max:4000']]);

        return $this->run($request, $ai, 'experience', 'Améliore la clarté de cette expérience sans ajouter de mission, d’outil ou de résultat qui n’y figure pas.', $data['text']);
    }

    public function skills(Request $request, Resume $resume, AiCareerService $ai): JsonResponse
    {
        $this->authorize('view', $resume);
        $content = ResumeContent::normalize($resume->content ?? []);
        $names = collect($content['skills'])->pluck('name')->filter()->implode(', ');

        return $this->run($request, $ai, 'skills', 'Reformule uniquement ces compétences, séparées par des virgules. N’en ajoute aucune.', $names);
    }

    public function grammar(Request $request, AiCareerService $ai): JsonResponse
    {
        $data = $request->validate(['text' => ['required', 'string', 'max:6000']]);

        return $this->run($request, $ai, 'grammar', 'Corrige uniquement la grammaire et l’orthographe. Ne change pas les faits.', $data['text']);
    }

    public function tone(Request $request, AiCareerService $ai): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:6000'],
            'tone' => ['required', 'in:classique,professionnel,direct,debutant'],
        ]);

        return $this->run($request, $ai, 'tone', 'Adapte le ton vers « '.$data['tone'].' » sans ajouter de fait.', $data['text']);
    }

    public function letter(Request $request, AiCareerService $ai): JsonResponse
    {
        $data = $request->validate([
            'resume_id' => ['nullable', 'integer'],
            'job_title' => ['required', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'offer' => ['nullable', 'string', 'max:5000'],
            'tone' => ['required', 'in:classique,professionnel,direct,debutant'],
            'length' => ['required', 'in:courte,moyenne,developpee'],
        ]);

        $source = $this->letterSource($request, $data);

        return $this->run(
            $request,
            $ai,
            'letter',
            'Rédige une lettre de motivation '.$data['length'].', ton '.$data['tone'].', pour le poste indiqué. Utilise seulement les faits du candidat. N’affirme aucune compétence absente.',
            $source,
        );
    }

    public function analyze(Request $request, AiCareerService $ai, QuotaService $quotas): JsonResponse
    {
        $data = $request->validate([
            'resume_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:8000'],
        ]);

        $resume = Resume::query()->whereKey($data['resume_id'])->where('user_id', $request->user()->id)->firstOrFail();
        $this->authorize('view', $resume);

        $offer = $request->user()->jobOffers()->create([
            'title' => $data['title'],
            'company' => $data['company'] ?? null,
            'description' => $data['description'],
        ]);

        $response = $this->run(
            $request,
            $ai,
            'analyze',
            'Compare l’offre et le CV. Dans proposition, liste les correspondances et les écarts. N’ajoute aucune compétence au candidat. Signale dans manquants ce qui est demandé par l’offre mais absent du CV.',
            "OFFRE\n".$offer->title."\n".$offer->company."\n".$offer->description."\n\nCV\n".$this->pack(ResumeContent::normalize($resume->content ?? [])),
        );

        return $response;
    }

    public function adapt(Request $request, Resume $resume, AiCareerService $ai, QuotaService $quotas): JsonResponse
    {
        $this->authorize('update', $resume);

        try {
            $quotas->assert($request->user(), 'adapt');
        } catch (QuotaExceededException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $data = $request->validate(['offer_id' => ['required', 'integer']]);
        $offer = JobOffer::query()->whereKey($data['offer_id'])->where('user_id', $request->user()->id)->firstOrFail();

        return $this->run(
            $request,
            $ai,
            'adapt',
            'Propose un résumé et des reformulations pour rapprocher le CV de l’offre, sans inventer d’expérience ni de compétence. La proposition reste à valider par le candidat.',
            "OFFRE\n".$offer->description."\n\nCV\n".$this->pack(ResumeContent::normalize($resume->content ?? [])),
        );
    }

    private function run(Request $request, AiCareerService $ai, string $feature, string $task, string $source): JsonResponse
    {
        try {
            $proposal = $ai->propose($request->user(), $feature, $task, $source);
        } catch (AiNotConfiguredException|QuotaExceededException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($proposal + [
            'notice' => 'Relisez la proposition. Elle n’est pas enregistrée tant que vous ne la validez pas.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function pack(array $content): string
    {
        return json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function letterSource(Request $request, array $data): string
    {
        $blocks = [
            'Poste : '.$data['job_title'],
            'Entreprise : '.($data['company'] ?? ''),
            'Offre : '.($data['offer'] ?? ''),
        ];

        if (! empty($data['resume_id'])) {
            $resume = Resume::query()->whereKey($data['resume_id'])->where('user_id', $request->user()->id)->first();

            if ($resume !== null) {
                $blocks[] = 'CV : '.$this->pack(ResumeContent::normalize($resume->content ?? []));
            }
        }

        $profile = $request->user()->profile;

        if ($profile !== null) {
            $blocks[] = 'Profil : '.trim($profile->headline.' '.$profile->summary);
        }

        return implode("\n", $blocks);
    }
}
