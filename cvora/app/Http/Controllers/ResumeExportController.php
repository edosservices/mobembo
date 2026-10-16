<?php

namespace App\Http\Controllers;

use App\Exceptions\QuotaExceededException;
use App\Models\Resume;
use App\Models\Setting;
use App\Services\Billing\QuotaService;
use App\Services\Resumes\DocxExporter;
use App\Services\Resumes\PdfExporter;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ResumeExportController extends Controller
{
    public function pdf(Request $request, Resume $resume, QuotaService $quotas, PdfExporter $exporter): Response
    {
        $this->guard($request, $resume, $quotas, 'pdf');

        return $exporter->download($resume);
    }

    public function docx(Request $request, Resume $resume, QuotaService $quotas, DocxExporter $exporter): BinaryFileResponse
    {
        $this->guard($request, $resume, $quotas, 'docx');

        return $exporter->download($resume);
    }

    private function guard(Request $request, Resume $resume, QuotaService $quotas, string $format): void
    {
        $this->authorize('view', $resume);

        if (! Setting::enabled('features.exports', true)) {
            throw ValidationException::withMessages([
                'export' => 'Les exportations sont désactivées.',
            ]);
        }

        try {
            $quotas->assert($request->user(), 'export', $format);
        } catch (QuotaExceededException $exception) {
            throw ValidationException::withMessages(['export' => $exception->getMessage()]);
        }
    }
}
