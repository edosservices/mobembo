<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Services\Resumes\ResumeWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_proposal_is_returned_and_not_written_into_the_resume(): void
    {
        config(['cvora.ai.key' => 'test-key']);
        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => ['content' => '{"proposition":"Analyste à Kinshasa.","manquants":["années d’expérience"],"notes":""}'],
                ]],
            ]),
        ]);

        $user = $this->member();
        $resume = app(ResumeWriter::class)->create($user, [
            'content' => ['summary' => 'Analyste.'],
        ]);

        $this->actingAs($user)
            ->postJson(route('assistant.summary', $resume))
            ->assertOk()
            ->assertJsonPath('proposition', 'Analyste à Kinshasa.');

        $this->assertSame('Analyste.', $resume->fresh()->content['summary']);
        Http::assertSent(fn ($request): bool => str_contains($request['messages'][0]['content'], 'Tu n\'inventes jamais'));
    }

    public function test_the_monthly_quota_stops_further_calls(): void
    {
        config(['cvora.ai.key' => 'test-key']);
        $user = $this->member();
        $resume = app(ResumeWriter::class)->create($user);

        foreach (range(1, 5) as $ignored) {
            AiUsageLog::query()->create([
                'user_id' => $user->id,
                'feature' => 'summary',
                'credits' => 1,
                'status' => 'success',
            ]);
        }

        Http::fake();

        $this->actingAs($user)
            ->postJson(route('assistant.summary', $resume))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Vous avez atteint le quota d’intelligence artificielle de votre offre pour ce mois.']);

        Http::assertNothingSent();
    }

    public function test_missing_api_key_does_not_pretend_to_generate_text(): void
    {
        $user = $this->member();
        $resume = app(ResumeWriter::class)->create($user);

        $this->actingAs($user)
            ->postJson(route('assistant.summary', $resume))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Le fournisseur d’IA n’est pas configuré. Ajoutez AI_API_KEY pour l’activer. Vous pouvez continuer à rédiger vous-même.']);
    }
}
