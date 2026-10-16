<?php

namespace Tests\Feature;

use App\Models\CoverLetter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoverLetterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_free_account_cannot_save_a_cover_letter(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post(route('letters.store'), $this->letter())
            ->assertSessionHasErrors('title');

        $this->assertSame(0, CoverLetter::query()->count());
    }

    public function test_a_premium_account_can_save_a_letter_and_keep_the_previous_text(): void
    {
        $user = $this->member('premium');

        $this->actingAs($user)->post(route('letters.store'), $this->letter())->assertRedirect();
        $letter = CoverLetter::query()->first();

        $this->actingAs($user)->put(route('letters.update', $letter), [
            ...$this->letter(),
            'content' => "Madame, Monsieur,\n\nJe présente mon parcours d’analyste.",
        ])->assertRedirect();

        $this->assertSame(1, $letter->versions()->count());
        $this->assertStringContainsString('parcours', $letter->fresh()->content);
    }

    /**
     * @return array<string, string>
     */
    private function letter(): array
    {
        return [
            'title' => 'Lettre analyste',
            'job_title' => 'Analyste',
            'company' => 'Société Kivu',
            'tone' => 'professionnel',
            'length' => 'courte',
            'content' => "Madame, Monsieur,\n\nJe souhaite le poste d’analyste.",
        ];
    }
}
