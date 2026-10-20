# Authelia OAuth for Flarum

A [Flarum](https://flarum.org) extension that adds [Authelia](https://www.authelia.com) as an OIDC login provider via [fof/oauth](https://github.com/FriendsOfFlarum/oauth).

Adapted from [sen-labs/oauth-authentik](https://github.com/sen-laboratories/flarum-oauth-authentik) for the Authelia identity provider.

## Requirements

- Flarum 2.0
- fof/oauth 2.0
- An Authelia instance with OIDC configured

## Installation

```bash
composer require sen-labs/oauth-authelia:"*"
php flarum cache:clear
```

For local/Docker development, add as a path repository:

```json
{
    "repositories": [
        { "type": "path", "url": "/path/to/flarum-oauth-authelia" }
    ]
}
```

## Configuration

In the Flarum admin panel under **Extensions → OAuth → Authelia**:

| Field | Value |
|-------|-------|
| Client ID | Your Authelia OIDC client ID (e.g. `flarum`) |
| Client Secret | Plaintext secret from Authelia config |
| External Base URL | `https://oni.sen-labs.org` (your Authelia public URL) |
| Internal Base URL | `http://authelia:9091` (optional, for Docker networking) |
| Scopes | `openid profile email groups sen` (optional, add custom scopes) |
| Redirect Path | `/tags` (optional, post-login redirect) |

## Email trust

The address from Authelia is treated as verified (the user does not have to confirm it again) unless the
`email_verified` claim is explicitly `false`; then it is only suggested at sign-up. This is the same decision FoF OAuth
uses for its optional email sync after login (`fof-oauth.update_email_from_provider`), through `getProviderVerifiedEmail()`.

## Authelia OIDC Client Config

In your Authelia `configuration.yml`, register the Flarum client:

```yaml
identity_providers:
  oidc:
    clients:
      - client_id: 'flarum'
        client_name: 'SEN Forum'
        client_secret: '<pbkdf2 digest>'
        redirect_uris:
          - 'https://onsen.sen-labs.org/auth/authelia'
        scopes:
          - 'openid'
          - 'profile'
          - 'email'
          - 'groups'
          - 'sen'          # custom scope for country/topics/is_admin
        claims_policy: 'sen_policy'
        response_types:
          - 'code'
        grant_types:
          - 'authorization_code'
        token_endpoint_auth_method: 'client_secret_post'
```

**Note**: The redirect URI must match exactly: `https://<your-flarum>/auth/authelia`

## License

MIT
