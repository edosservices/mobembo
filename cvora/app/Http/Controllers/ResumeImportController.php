<?php

namespace App\Http\Controllers;

use App\Exceptions\QuotaExceededException;
use App\Models\Resume;
use App\Models\ResumeImport;
use App\Models\Setting;
use App\Services\Billing\QuotaService;
use App\Services\Resumes\ResumeContent;
use App\Services\Resumes\ResumeImporter;
use App\Services\Resumes\ResumeWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ResumeImportController extends Controller
{
    public function create(Request $request, QuotaService $quotas): View
    {
        $this->ensureEnabled($request, $quotas);

        return view('resumes.import', [
            'resumes' => $request->user()->resumes()->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request, ResumeImporter $importer, QuotaService $quotas): RedirectResponse
    {
        $this->ensureEnabled($request, $quotas);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:pdf,docx'],
        ], [
            'file.required' => 'Choisissez un fichier PDF ou DOCX.',
            'file.max' => 'Le fichier dépasse 5 Mo.',
            'file.extensions' => 'Seuls les fichiers PDF et DOCX sont acceptés.',
        ]);

        try {
            $inspected = $importer->inspect($data['file']);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $path = $data['file']->store('imports', 'local');
        $import = $request->user()->resumeImports()->create([
            'original_name' => $data['file']->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime' => $data['file']->getMimeType(),
            'status' => 'pending',
            'extracted_text' => $inspected['text'],
            'detected' => $inspected['content'],
            'warning' => $inspected['warning'],
        ]);

        return redirect()->route('resumes.import.review', $import);
    }

    public function review(ResumeImport $import): View
    {
        $this->authorize('view', $import);
        abort_unless($import->status === 'pending', 404);

        return view('resumes.import-review', [
            'import' => $import,
            'content' => ResumeContent::normalize($import->detected ?? []),
            'resumes' => $import->user->resumes()->orderBy('title')->get(),
        ]);
    }

    public function confirm(Request $request, ResumeImport $import, ResumeWriter $writer): RedirectResponse
    {
        $this->authorize('update', $import);
        abort_unless($import->status === 'pending', 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'replace_resume_id' => ['nullable', 'integer'],
            'confirm_replace' => ['nullable', 'boolean'],
            ...ResumeContent::rules(),
        ]);

        $content = ResumeContent::normalize($data['content']);
        $user = $request->user();

        if ($request->filled('replace_resume_id')) {
            if (! $request->boolean('confirm_replace')) {
                throw ValidationException::withMessages([
                    'confirm_replace' => 'Cochez la confirmation pour remplacer un CV existant.',
                ]);
            }

            $resume = Resume::query()->whereKey($data['replace_resume_id'])->where('user_id', $user->id)->firstOrFail();
            $writer->save($user, $resume, [
                'title' => $data['title'],
                'template_slug' => $resume->template?->slug ?? 'moderne',
                'accent_color' => $resume->accent_color,
                'content' => $content,
            ], true, 'import');
        } else {
            $resume = $writer->create($user, [
                'title' => $data['title'],
                'content' => $content,
            ]);
        }

        Storage::disk($import->disk)->delete($import->path);
        $import->forceFill([
            'status' => 'confirmed',
            'resume_id' => $resume->id,
            'path' => '',
            'extracted_text' => null,
        ])->save();

        return redirect()->route('resumes.edit', $resume)->with('status', 'CV enregistré à partir du fichier importé.');
    }

    private function ensureEnabled(Request $request, QuotaService $quotas): void
    {
        if (! Setting::enabled('features.imports', true)) {
            throw ValidationException::withMessages([
                'file' => 'L’importation est désactivée.',
            ]);
        }

        try {
            $quotas->assert($request->user(), 'import');
        } catch (QuotaExceededException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
    }
}
