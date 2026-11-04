<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalNetworkTest extends TestCase
{
    public function test_direct_lan_requests_ignore_forwarded_headers(): void
    {
        config(['trustedproxy.proxies' => '']);

        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.20'])
            ->withHeaders([
                'Host' => '192.168.1.50',
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'external.example',
            ])
            ->get('http://192.168.1.50/')
            ->assertRedirect('http://192.168.1.50/admin/login');
    }

    public function test_explicit_proxy_can_preserve_https_for_existing_deployments(): void
    {
        config(['trustedproxy.proxies' => '192.168.1.10']);

        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.10'])
            ->withHeaders([
                'Host' => '192.168.1.50',
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'damios.example',
                'X-Forwarded-Port' => '443',
            ])
            ->get('http://192.168.1.50/')
            ->assertRedirect('https://damios.example/admin/login');
    }
}
