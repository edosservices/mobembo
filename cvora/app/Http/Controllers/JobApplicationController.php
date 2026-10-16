<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Exceptions\QuotaExceededException;
use App\Models\JobApplication;
use App\Services\Billing\QuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class JobApplicationController extends Controller
{
    public function index(Request $request): View
    {
        return view('applications.index', [
            'applications' => $request->user()->applications()->with(['resume', 'coverLetter', 'jobOffer'])->latest()->paginate(15),
            'resumes' => $request->user()->resumes()->orderBy('title')->get(),
            'letters' => $request->user()->coverLetters()->orderBy('title')->get(),
            'offers' => $request->user()->jobOffers()->latest()->get(),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }

    public function store(Request $request, QuotaService $quotas): RedirectResponse
    {
        try {
            $quotas->assert($request->user(), 'create_application');
        } catch (QuotaExceededException $exception) {
            throw ValidationException::withMessages(['notes' => $exception->getMessage()]);
        }

        $data = $this->validated($request);
        $request->user()->applications()->create($data);

        return redirect()->route('applications.index')->with('status', 'Candidature ajoutée.');
    }

    public function update(Request $request, JobApplication $application): RedirectResponse
    {
        $this->authorize('update', $application);
        $application->update($this->validated($request));

        return redirect()->route('applications.index')->with('status', 'Candidature mise à jour.');
    }

    public function destroy(JobApplication $application): RedirectResponse
    {
        $this->authorize('delete', $application);
        $application->delete();

        return redirect()->route('applications.index')->with('status', 'Candidature retirée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'resume_id' => ['nullable', 'integer'],
            'cover_letter_id' => ['nullable', 'integer'],
            'job_offer_id' => ['nullable', 'integer'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'applied_at' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        foreach (['resume_id' => 'resumes', 'cover_letter_id' => 'coverLetters', 'job_offer_id' => 'jobOffers'] as $field => $relation) {
            if (! empty($data[$field]) && ! $user->{$relation}()->whereKey($data[$field])->exists()) {
                $data[$field] = null;
            }
        }

        return $data;
    }
}
