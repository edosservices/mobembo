<?php

namespace Tests\Feature;

use App\Services\Resumes\ResumeWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_free_offer_does_not_download_a_pdf(): void
    {
        $user = $this->member();
        $resume = app(ResumeWriter::class)->create($user);

        $this->actingAs($user)
            ->get(route('resumes.export.pdf', $resume))
            ->assertSessionHasErrors('export');
    }

    public function test_a_premium_member_downloads_a_pdf_and_a_docx(): void
    {
        $user = $this->member('premium');
        $resume = app(ResumeWriter::class)->create($user, [
            'title' => 'CV export',
            'content' => [
                'personal' => ['first_name' => 'Amina', 'last_name' => 'Diallo', 'title' => 'Analyste'],
                'summary' => 'Analyse des données à Kinshasa.',
            ],
        ]);

        $pdf = $this->actingAs($user)->get(route('resumes.export.pdf', $resume));
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $docx = $this->actingAs($user)->get(route('resumes.export.docx', $resume));
        $docx->assertOk();
        $path = $docx->baseResponse->getFile()->getPathname();
        $this->assertStringStartsWith('PK', (string) file_get_contents($path));
    }
}
