<?php

namespace Tests\Feature;

use App\Models\Resume;
use App\Services\Resumes\ResumeWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Tests\TestCase;

class ResumeImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_docx_import_is_confirmed_before_a_resume_is_created(): void
    {
        $user = $this->member('premium');
        $path = storage_path('framework/testing-cv.docx');
        $document = new PhpWord;
        $section = $document->addSection();
        $section->addText('Amina Diallo');
        $section->addText('amina.diallo@example.com');
        $section->addText('Expérience');
        $section->addText("Analyste\nSociété Kivu\nSuivi des indicateurs.");
        IOFactory::createWriter($document, 'Word2007')->save($path);

        $upload = new UploadedFile($path, 'cv.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->actingAs($user)->post(route('resumes.import.store'), ['file' => $upload]);
        $response->assertRedirect();
        $this->assertSame(0, Resume::query()->count());

        $review = $this->actingAs($user)->get($response->headers->get('Location'));
        $review->assertOk();
        $review->assertSee('amina.diallo@example.com');

        $importId = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));

        $this->actingAs($user)->post(route('resumes.import.confirm', $importId), [
            'title' => 'CV importé',
            'content' => [
                'personal' => [
                    'first_name' => 'Amina',
                    'last_name' => 'Diallo',
                    'email' => 'amina.diallo@example.com',
                ],
                'summary' => 'Confirmé par la candidate.',
            ],
        ])->assertRedirect();

        $this->assertSame('amina.diallo@example.com', Resume::query()->first()->content['personal']['email']);
    }

    public function test_a_scanned_pdf_warns_that_no_text_was_found(): void
    {
        $user = $this->member('premium');
        $binary = Pdf::loadHTML('<html><body></body></html>')->output();
        $file = UploadedFile::fake()->createWithContent('scan.pdf', $binary);

        $response = $this->actingAs($user)->post(route('resumes.import.store'), ['file' => $file]);
        $this->actingAs($user)->get($response->headers->get('Location'))
            ->assertSee('ne contient pas de texte exploitable');
    }

    public function test_an_existing_resume_is_not_replaced_without_confirmation(): void
    {
        $user = $this->member('premium');
        $resume = app(ResumeWriter::class)->create($user, [
            'title' => 'Original',
            'content' => ['summary' => 'Texte original.'],
        ]);
        $binary = Pdf::loadHTML('<p>Autre personne autre@example.com</p><p>Experience</p><p>Un poste décrit sur plusieurs mots utiles.</p>')->output();
        $file = UploadedFile::fake()->createWithContent('cv.pdf', $binary);
        $stored = $this->actingAs($user)->post(route('resumes.import.store'), ['file' => $file]);
        $importId = basename(parse_url($stored->headers->get('Location'), PHP_URL_PATH));

        $this->actingAs($user)->post(route('resumes.import.confirm', $importId), [
            'title' => 'Remplacement',
            'replace_resume_id' => $resume->id,
            'content' => [
                'personal' => ['first_name' => 'Autre'],
                'summary' => 'Nouveau texte.',
            ],
        ])->assertSessionHasErrors('confirm_replace');

        $this->assertSame('Texte original.', $resume->fresh()->content['summary']);
    }
}
