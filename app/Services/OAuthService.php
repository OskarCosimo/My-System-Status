<?php
// path: app/Services/OAuthService.php

namespace App\Services;

use App\Services\SettingService;
use Exception;

class OAuthService
{
    private string $provider;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(string $provider)
    {
        $this->provider = strtolower($provider);
        $this->clientId = SettingService::get("oauth_{$provider}_client_id", '');
        $this->clientSecret = SettingService::get("oauth_{$provider}_client_secret", '');
        
        // Auto-detect redirect callback URL dynamically with clean trailing slash trim
        $baseUrl = rtrim(SettingService::getAppUrl(), '/');
        $this->redirectUri = "{$baseUrl}/auth/callback/{$this->provider}";
    }

    /**
     * Generate the authorization redirect URL.
     */
    public function getAuthorizationUrl(): string
    {
        return match ($this->provider) {
            'myetv' => "https://developers.myetv.tv/api/oauth/authorize.php?" . http_build_query([
                'client_id'     => $this->clientId,
                'redirect_uri'  => $this->redirectUri,
                'response_type' => 'code',
                'scope'         => 'profile email'
            ]),
            'google' => "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
                'client_id'     => $this->clientId,
                'redirect_uri'  => $this->redirectUri,
                'response_type' => 'code',
                'scope'         => 'openid profile email'
            ]),
            'microsoft' => "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query([
                'client_id'     => $this->clientId,
                'redirect_uri'  => $this->redirectUri,
                'response_type' => 'code',
                'scope'         => 'openid profile email User.Read'
            ]),
            'facebook' => "https://www.facebook.com/v19.0/dialog/oauth?" . http_build_query([
                'client_id'     => $this->clientId,
                'redirect_uri'  => $this->redirectUri,
                'scope'         => 'email,public_profile'
            ]),
            default => throw new Exception("Unsupported OAuth provider: {$this->provider}")
        };
    }

    /**
     * Handle authorization code exchange and return normalized user data.
     */
    public function handleCallback(string $code): array
    {
        $tokenData = $this->exchangeCodeForToken($code);
        $accessToken = $tokenData['access_token'] ?? throw new Exception("Failed to retrieve access token.");

        return $this->fetchUserProfile($accessToken);
    }

    private function exchangeCodeForToken(string $code): array
    {
        $tokenUrl = match ($this->provider) {
            'myetv'     => 'https://developers.myetv.tv/api/oauth/token.php',
            'google'    => 'https://oauth2.googleapis.com/token',
            'microsoft' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'facebook'  => 'https://graph.facebook.com/v19.0/oauth/access_token',
        };

        $postParams = [
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postParams),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("Token exchange failed with response: {$response}");
        }

        return json_decode((string)$response, true) ?? [];
    }

    private function fetchUserProfile(string $accessToken): array
    {
        $profileUrl = match ($this->provider) {
            'myetv'     => 'https://developers.myetv.tv/api/v1/profile.php',
            'google'    => 'https://www.googleapis.com/oauth2/v3/userinfo',
            'microsoft' => 'https://graph.microsoft.com/v1.0/me',
            'facebook'  => 'https://graph.facebook.com/me?fields=id,name,email,picture'
        };

        $ch = curl_init($profileUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$accessToken}",
                "Accept: application/json"
            ],
            CURLOPT_TIMEOUT        => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Unable to fetch user profile data. HTTP {$httpCode}: {$response}");
        }

        $data = json_decode((string)$response, true);

        // Normalize data across all providers with strict email validation
        $rawEmail = trim((string)($data['email'] ?? ''));
        $validEmail = (filter_var($rawEmail, FILTER_VALIDATE_EMAIL) && !str_contains(strtolower($rawEmail), 'fail')) ? $rawEmail : null;

        return match ($this->provider) {
            'myetv' => [
                'provider'    => 'myetv',
                'provider_id' => (string)($data['id'] ?? ''),
                'email'       => $validEmail,
                'name'        => $data['name'] ?? ($data['username'] ?? 'MYETV User'),
                'avatar'      => $data['avatar'] ?? null,
                'profile_url' => $data['profile_url'] ?? null
            ],
            'google' => [
                'provider'    => 'google',
                'provider_id' => (string)$data['sub'],
                'email'       => $data['email'],
                'name'        => $data['name'],
                'avatar'      => $data['picture'] ?? null,
                'profile_url' => null
            ],
            'microsoft' => [
                'provider'    => 'microsoft',
                'provider_id' => (string)$data['id'],
                'email'       => $data['userPrincipalName'] ?? $data['mail'],
                'name'        => $data['displayName'],
                'avatar'      => null,
                'profile_url' => null
            ],
            'facebook' => [
                'provider'    => 'facebook',
                'provider_id' => (string)$data['id'],
                'email'       => $validEmail,
                'name'        => $data['name'],
                'avatar'      => $data['picture']['data']['url'] ?? null,
                'profile_url' => null
            ]
        };
    }
}
