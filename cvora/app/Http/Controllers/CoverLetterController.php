<?php

namespace App\Http\Controllers;

use App\Exceptions\QuotaExceededException;
use App\Http\Requests\StoreCoverLetterRequest;
use App\Models\CoverLetter;
use App\Models\CoverLetterVersion;
use App\Services\Billing\QuotaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class CoverLetterController extends Controller
{
    public function index(Request $request): View
    {
        return view('letters.index', [
            'letters' => $request->user()->coverLetters()->latest('updated_at')->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        return view('letters.edit', [
            'letter' => new CoverLetter([
                'title' => 'Lettre de motivation',
                'tone' => 'professionnel',
                'length' => 'moyenne',
                'content' => '',
            ]),
            'resumes' => $request->user()->resumes()->orderBy('title')->get(),
            'offers' => $request->user()->jobOffers()->latest()->get(),
        ]);
    }

    public function store(StoreCoverLetterRequest $request, QuotaService $quotas): RedirectResponse
    {
        try {
            $quotas->assert($request->user(), 'create_letter');
        } catch (QuotaExceededException $exception) {
            throw ValidationException::withMessages(['title' => $exception->getMessage()]);
        }

        $letter = $request->user()->coverLetters()->create($this->payload($request));

        return redirect()->route('letters.edit', $letter)->with('status', 'Lettre enregistrée.');
    }

    public function edit(Request $request, CoverLetter $letter): View
    {
        $this->authorize('update', $letter);

        return view('letters.edit', [
            'letter' => $letter,
            'resumes' => $request->user()->resumes()->orderBy('title')->get(),
            'offers' => $request->user()->jobOffers()->latest()->get(),
            'versions' => $letter->versions()->latest('id')->limit(10)->get(),
        ]);
    }

    public function update(StoreCoverLetterRequest $request, CoverLetter $letter): RedirectResponse
    {
        if ($letter->content !== $request->string('content')->toString()) {
            CoverLetterVersion::query()->create([
                'cover_letter_id' => $letter->id,
                'label' => 'Avant modification',
                'source' => 'manual',
                'snapshot' => $letter->content,
                'created_at' => now(),
            ]);
        }

        $letter->update($this->payload($request));

        return redirect()->route('letters.edit', $letter)->with('status', 'Lettre enregistrée.');
    }

    public function destroy(CoverLetter $letter): RedirectResponse
    {
        $this->authorize('delete', $letter);
        $letter->delete();

        return redirect()->route('letters.index')->with('status', 'Lettre supprimée.');
    }

    public function preview(CoverLetter $letter): View
    {
        $this->authorize('view', $letter);

        return view('letters.print', ['letter' => $letter]);
    }

    public function pdf(Request $request, CoverLetter $letter, QuotaService $quotas): Response
    {
        $this->authorize('view', $letter);
        $this->assertExport($request, $quotas);

        return Pdf::loadView('letters.print', ['letter' => $letter])
            ->setPaper('a4')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isRemoteEnabled', false)
            ->download((Str::slug($letter->title) ?: 'lettre').'.pdf');
    }

    public function docx(Request $request, CoverLetter $letter, QuotaService $quotas): BinaryFileResponse
    {
        $this->authorize('view', $letter);
        $this->assertExport($request, $quotas);

        $document = new PhpWord;
        $document->setDefaultFontName('Calibri');
        $section = $document->addSection();
        $section->addText($letter->title, ['bold' => true, 'size' => 16]);
        $section->addText(trim(($letter->company ?? '').' '.($letter->job_title ?? '')), ['italic' => true, 'size' => 11]);

        foreach (preg_split("/\n\s*\n/", trim($letter->content)) ?: [] as $paragraph) {
            $section->addText(trim($paragraph), ['size' => 11], ['spaceAfter' => 160]);
        }

        $path = tempnam(sys_get_temp_dir(), 'cvora');
        IOFactory::createWriter($document, 'Word2007')->save($path);

        return response()->download($path, (Str::slug($letter->title) ?: 'lettre').'.docx')->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StoreCoverLetterRequest $request): array
    {
        $data = $request->safe()->only([
            'title', 'job_title', 'company', 'offer_excerpt', 'tone', 'length', 'content', 'resume_id', 'job_offer_id',
        ]);
        $user = $request->user();

        if (! empty($data['resume_id']) && ! $user->resumes()->whereKey($data['resume_id'])->exists()) {
            $data['resume_id'] = null;
        }

        if (! empty($data['job_offer_id']) && ! $user->jobOffers()->whereKey($data['job_offer_id'])->exists()) {
            $data['job_offer_id'] = null;
        }

        return $data;
    }

    private function assertExport(Request $request, QuotaService $quotas): void
    {
        try {
            $quotas->assert($request->user(), 'export', 'pdf');
        } catch (QuotaExceededException $exception) {
            throw ValidationException::withMessages(['export' => $exception->getMessage()]);
        }
    }
}
