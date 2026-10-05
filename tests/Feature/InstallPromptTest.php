<?php

namespace Tests\Feature;

use Tests\TestCase;

class InstallPromptTest extends TestCase
{
    public function test_registration_offers_an_android_home_screen_install(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Télécharger l')
            ->assertSee('APK')
            ->assertDontSee('application officielle')
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('sw.js', false);

        $this->get(route('pwa.manifest'))
            ->assertOk()
            ->assertHeader('content-type', 'application/manifest+json')
            ->assertJsonPath('name', 'ZELVORA')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('icons.0.src', '/images/icons/icon-192.png');

        $this->get('/sw.js')
            ->assertOk()
            ->assertSee('fetch', false);
    }
}
