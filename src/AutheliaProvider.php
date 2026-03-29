<?php

namespace SenLabs\OAuth\Authelia;

use FoF\OAuth\Provider;
use Flarum\Forum\Auth\Registration;
use Flarum\Http\UrlGenerator;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\GenericProvider;

class AutheliaProvider extends Provider
{
    public function name(): string { return 'authelia'; }
    public function icon(): string { return 'fas fa-lock'; }
    public function type(): string { return 'openid'; }
    public function link(): string { return 'https://www.authelia.org'; }
    public function pkceEnabled(): bool { return true; }
  
    public function fields(): array
    {
        return [
            'client_id'     => ['required'],
            'client_secret' => ['required'],
            'base_url'      => ['required'],
            'internal_url'  => [],  // optional: Docker-internal URL (e.g. http://authelia:9091)
            'redirect_path' => [],  // optional: post-login redirect
            'scopes'        => [],  // optional: defaults to 'openid profile email groups'
        ];
    }

    public function provider(string $callbackUrl): AbstractProvider
    {
        $externalUrl = rtrim($this->getSetting('base_url'), '/');
        $internalUrl = rtrim($this->getSetting('internal_url') ?: $externalUrl, '/');
        $scopes      = $this->getSetting('scopes') ?: 'openid profile email groups';

        // Authelia uses standard OIDC endpoint paths
        return new GenericProvider([
            'clientId'                => $this->getSetting('client_id'),
            'clientSecret'            => $this->getSetting('client_secret'),
            'redirectUri'             => $callbackUrl,
            'urlAuthorize'            => "$externalUrl/api/oidc/authorization",
            'urlAccessToken'          => "$internalUrl/api/oidc/token",
            'urlResourceOwnerDetails' => "$internalUrl/api/oidc/userinfo",
            'scopes'                  => $scopes,
            'scopeSeparator'          => ' ',
            'responseResourceOwnerId' => 'sub',
        ]);
    }

public function suggestions(Registration $registration, $user, string $token): void
{
    $url  = resolve(UrlGenerator::class);
    $data = $user->toArray();

    $rawUsername = $data['preferred_username']
        ?? $data['name']
        ?? explode('@', $data['email'] ?? 'unknown')[0];

    $username = str_replace('.', '-', $rawUsername);

    if (!empty($data['email'])) {
        $registration->provideTrustedEmail($data['email']);
    }

    $registration->provide('username', $username);

    $targetPath = $this->getSetting('redirect_path') ?: 'tags';
    $payload = array_merge($data, [
        'redirectTo' => $url->to('forum')->path($targetPath),
        'source'     => 'authelia',
    ]);

    $registration->setPayload($payload);
}

