<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_introduces_zelvora(): void
    {
        Project::query()->create([
            'name' => 'Résidence Absente',
            'slug' => 'residence-absente',
            'description' => 'Projet de test.',
            'location' => 'Kinshasa',
            'category' => 'Résidentiel',
            'target_amount' => '1000.00',
            'funded_amount' => '0.00',
            'min_investment' => '10.00',
            'duration_days' => 30,
            'expected_return_percent' => '5.0000',
            'distribution_frequency' => 'at_maturity',
            'economic_terms' => 'Estimation.',
            'status' => ProjectStatus::Active,
            'is_demo' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('ZELVORA')
            ->assertSee('Votre patrimoine commence ici.')
            ->assertSee('Des adresses que l’on a envie de retenir.')
            ->assertDontSee('Résidence Absente')
            ->assertDontSee('Comment effectuer un dépôt');

        $this->get(route('faq'))
            ->assertOk()
            ->assertDontSee('Comment effectuer un dépôt');
    }
}
