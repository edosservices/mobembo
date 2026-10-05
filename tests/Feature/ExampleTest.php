<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_introduces_zelvora(): void
    {
        $this->get('/')->assertOk()->assertSee('ZELVORA')->assertSee('Votre patrimoine commence ici.');
    }
}
