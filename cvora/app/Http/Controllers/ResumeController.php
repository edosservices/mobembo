<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveResumeRequest;
use App\Models\Resume;
use App\Models\ResumeTemplate;
use App\Models\ResumeVersion;
use App\Services\Billing\QuotaService;
use App\Services\Resumes\ResumeContent;
use App\Services\Resumes\ResumeWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResumeController extends Controller
{
    public function index(Request $request): View
    {
        return view('resumes.index', [
            'resumes' => $request->user()->resumes()->with('template')->latest('updated_at')->paginate(12),
        ]);
    }

    public function store(Request $request, ResumeWriter $writer): RedirectResponse
    {
        $resume = $writer->create($request->user());

        return redirect()->route('resumes.edit', $resume);
    }

    public function edit(Request $request, Resume $resume, QuotaService $quotas): View
    {
        $this->authorize('update', $resume);
        $resume->load('template');
        $templates = ResumeTemplate::query()->active()->get();

        return view('resumes.edit', [
            'resume' => $resume,
            'templates' => $templates,
            'allowed' => $templates->mapWithKeys(fn (ResumeTemplate $template): array => [
                $template->slug => $quotas->templateAllowedFor($request->user(), $template),
            ]),
            'state' => [
                'title' => $resume->title,
                'template_slug' => $resume->template?->slug ?? 'moderne',
                'accent_color' => ResumeContent::accent($resume->accent_color),
                'content' => ResumeContent::normalize($resume->content ?? []),
            ],
        ]);
    }

    public function update(SaveResumeRequest $request, Resume $resume, ResumeWriter $writer): RedirectResponse
    {
        $writer->save(
            $request->user(),
            $resume,
            $request->validated(),
            $request->boolean('record_version'),
            'manual',
        );

        return redirect()->route('resumes.edit', $resume)->with('status', 'CV enregistré.');
    }

    public function autosave(SaveResumeRequest $request, Resume $resume, ResumeWriter $writer): JsonResponse
    {
        $saved = $writer->save(
            $request->user(),
            $resume,
            $request->validated(),
            $request->boolean('record_version'),
            $request->boolean('record_version') ? 'manual' : 'autosave',
        );

        return response()->json([
            'saved_at' => $saved->updated_at?->timezone(config('app.timezone'))->format('H:i'),
        ]);
    }

    public function destroy(Request $request, Resume $resume): RedirectResponse
    {
        $this->authorize('delete', $resume);
        $resume->delete();

        return redirect()->route('resumes.index')->with('status', 'CV supprimé.');
    }

    public function duplicate(Request $request, Resume $resume, ResumeWriter $writer): RedirectResponse
    {
        $this->authorize('view', $resume);
        $copy = $writer->duplicate($request->user(), $resume);

        return redirect()->route('resumes.edit', $copy)->with('status', 'CV dupliqué.');
    }

    public function preview(Resume $resume): View
    {
        $this->authorize('view', $resume);
        $resume->load('template');

        return view('resumes.print', [
            'content' => ResumeContent::normalize($resume->content ?? []),
            'template' => $resume->template?->slug ?? 'moderne',
            'accent' => ResumeContent::accent($resume->accent_color),
            'watermark' => null,
        ]);
    }

    public function versions(Request $request, Resume $resume, QuotaService $quotas): View
    {
        $this->authorize('view', $resume);

        return view('resumes.versions', [
            'resume' => $resume,
            'versions' => $resume->versions()->latest('id')->get(),
            'allowed' => $quotas->limits($request->user())['versions'] ?? false,
        ]);
    }

    public function restore(Request $request, Resume $resume, ResumeVersion $version, ResumeWriter $writer): RedirectResponse
    {
        $this->authorize('update', $resume);
        abort_unless($version->resume_id === $resume->id, 404);
        $writer->restore($request->user(), $resume, $version);

        return redirect()->route('resumes.edit', $resume)->with('status', 'Version restaurée. L’ancienne version a été conservée.');
    }
}
