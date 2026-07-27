<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GoogleDriveAuthorizationController extends Controller
{
    private const AUTHORIZATION_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const DRIVE_SCOPE = 'https://www.googleapis.com/auth/drive';

    public function redirect(Request $request): RedirectResponse
    {
        if ($redirect = $this->redirectGuest($request)) {
            return $redirect;
        }

        $this->authorizeGerencia($request);
        $this->ensureConfigured();

        $state = Str::random(64);
        $request->session()->put('google_drive_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google_drive.client_id'),
            'redirect_uri' => config('services.google_drive.redirect_uri'),
            'response_type' => 'code',
            'scope' => self::DRIVE_SCOPE,
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away(self::AUTHORIZATION_URL.'?'.$query);
    }

    public function callback(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectGuest($request)) {
            return $redirect;
        }

        $this->authorizeGerencia($request);
        $this->ensureConfigured();

        if ($request->filled('error')) {
            return view('google-drive.authorization-result', [
                'error' => 'Google no autorizó la conexión: '.$request->string('error')->toString(),
                'refreshToken' => null,
            ]);
        }

        $expectedState = $request->session()->pull('google_drive_oauth_state');

        abort_unless(
            is_string($expectedState)
                && $expectedState !== ''
                && hash_equals($expectedState, $request->string('state')->toString()),
            419,
            'La autorización venció o no corresponde a esta sesión.',
        );

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $token = Http::asForm()
                ->timeout(20)
                ->post(self::TOKEN_URL, [
                    'client_id' => config('services.google_drive.client_id'),
                    'client_secret' => config('services.google_drive.client_secret'),
                    'code' => $request->string('code')->toString(),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => config('services.google_drive.redirect_uri'),
                ])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            report($exception);

            return view('google-drive.authorization-result', [
                'error' => 'Google rechazó el intercambio del código. Revisa que el URI de redirección coincida exactamente.',
                'refreshToken' => null,
            ]);
        }

        $refreshToken = $token['refresh_token'] ?? null;

        return view('google-drive.authorization-result', [
            'error' => $refreshToken
                ? null
                : 'Google no devolvió un refresh token. Revoca el acceso anterior de DAMIAN OS en tu cuenta de Google y vuelve a conectar.',
            'refreshToken' => $refreshToken,
        ]);
    }

    private function authorizeGerencia(Request $request): void
    {
        abort_unless($request->user()->role === 'gerencia', 403);
    }

    private function redirectGuest(Request $request): ?RedirectResponse
    {
        return $request->user()
            ? null
            : redirect()->route('filament.admin.auth.login');
    }

    private function ensureConfigured(): void
    {
        abort_if(
            blank(config('services.google_drive.client_id'))
                || blank(config('services.google_drive.client_secret'))
                || blank(config('services.google_drive.redirect_uri')),
            503,
            'La conexión con Google Drive todavía no está configurada.',
        );
    }
}
