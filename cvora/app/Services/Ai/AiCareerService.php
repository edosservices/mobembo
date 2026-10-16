<?php

namespace App\Services\Ai;

use App\Exceptions\AiNotConfiguredException;
use App\Exceptions\QuotaExceededException;
use App\Models\AiUsageLog;
use App\Models\Setting;
use App\Models\SystemEvent;
use App\Models\User;
use App\Services\Billing\QuotaService;
use Illuminate\Support\Facades\Http;

class AiCareerService
{
    public function __construct(
        private readonly PromptLibrary $prompts,
        private readonly QuotaService $quotas,
    ) {}

    public function configured(): bool
    {
        return is_string(config('cvora.ai.key')) && config('cvora.ai.key') !== '';
    }

    /**
     * @return array{proposition: string, manquants: list<string>, notes: string}
     */
    public function propose(User $user, string $feature, string $task, string $source): array
    {
        if (! Setting::enabled('features.ai', true)) {
            throw new AiNotConfiguredException('L’assistant est désactivé par l’administrateur.');
        }

        if (! $this->configured()) {
            throw new AiNotConfiguredException('Le fournisseur d’IA n’est pas configuré. Ajoutez AI_API_KEY pour l’activer. Vous pouvez continuer à rédiger vous-même.');
        }

        try {
            $this->quotas->assert($user, 'ai');
        } catch (QuotaExceededException $exception) {
            $this->log($user, $feature, 'denied', 0);

            throw $exception;
        }

        $base = (string) (Setting::getValue('ai.base_url') ?: config('cvora.ai.base_url'));
        $model = (string) (Setting::getValue('ai.model') ?: config('cvora.ai.model'));

        try {
            $response = Http::withToken((string) config('cvora.ai.key'))
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout((int) config('cvora.ai.timeout'))
                ->post(rtrim($base, '/').'/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->prompts->system()],
                        ['role' => 'user', 'content' => $this->prompts->user($task, $source)],
                    ],
                ]);
        } catch (\Throwable $exception) {
            $this->log($user, $feature, 'error', 0);
            SystemEvent::record('error', 'ai', 'Délai ou connexion impossible vers le fournisseur.');

            throw new AiNotConfiguredException('Le fournisseur d’IA n’a pas répondu. Aucune modification n’a été appliquée.');
        }

        if (! $response->successful()) {
            $this->log($user, $feature, 'error', 0);
            SystemEvent::record('error', 'ai', 'Le fournisseur a renvoyé une erreur.', [
                'status' => $response->status(),
            ]);

            throw new AiNotConfiguredException('Le fournisseur d’IA a renvoyé une erreur. Aucune modification n’a été appliquée.');
        }

        $parsed = $this->parse((string) $response->json('choices.0.message.content'));
        $this->log($user, $feature, 'success', 1);

        return $parsed;
    }

    /**
     * @return array{proposition: string, manquants: list<string>, notes: string}
     */
    private function parse(string $content): array
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        $json = $start === false || $end === false ? '' : substr($content, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        if (! is_array($decoded) || ! is_string($decoded['proposition'] ?? null)) {
            throw new AiNotConfiguredException('La réponse de l’IA n’est pas exploitable. Aucune modification n’a été appliquée.');
        }

        $missing = [];

        foreach ($decoded['manquants'] ?? [] as $item) {
            if (is_string($item) && $item !== '') {
                $missing[] = mb_substr($item, 0, 180);
            }
        }

        return [
            'proposition' => mb_substr(trim($decoded['proposition']), 0, 6000),
            'manquants' => $missing,
            'notes' => mb_substr(trim((string) ($decoded['notes'] ?? '')), 0, 500),
        ];
    }

    private function log(User $user, string $feature, string $status, int $credits): void
    {
        AiUsageLog::query()->create([
            'user_id' => $user->id,
            'feature' => $feature,
            'credits' => $credits,
            'status' => $status,
        ]);
    }
}
