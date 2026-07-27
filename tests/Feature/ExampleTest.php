<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_the_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_assets_use_https_behind_a_trusted_proxy(): void
    {
        $response = $this
            ->withHeaders([
                'X-Forwarded-Host' => 'os.damiancompany.com.pe',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('/admin/login');

        $response
            ->assertOk()
            ->assertSee('https://os.damiancompany.com.pe/build/assets/', false);
    }
}
