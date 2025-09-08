<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WordPressService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirect;
    protected string $siteId;

    public function __construct()
    {
        $this->clientId = config('services.wordpress.client_id');
        $this->clientSecret = config('services.wordpress.client_secret');
        $this->redirect = config('services.wordpress.redirect');
        $this->siteId = config('services.wordpress.site_id');
    }

    public function getAuthUrl(string $state = null): string
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirect,
            'response_type' => 'code',
            'scope' => 'auth',
            'state' => $state ?? Str::random(40),
        ]);
        return "https://public-api.wordpress.com/oauth2/authorize?{$params}";
    }

    public function exchangeCodeForToken(string $code): array
    {
        $resp = Http::asForm()->post('https://public-api.wordpress.com/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirect,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);
        return $resp->successful() ? $resp->json() : [];
    }

    public function getUserSites(string $accessToken): array
    {
        $resp = Http::withToken($accessToken)->get('https://public-api.wordpress.com/rest/v1.1/me/sites');
        return $resp->successful() ? $resp->json() : [];
    }

    public function getMe(string $accessToken): array
    {
        $resp = Http::withToken($accessToken)->get('https://public-api.wordpress.com/rest/v1.1/me');
        return $resp->successful() ? $resp->json() : [];
    }

    public function listPosts(string $accessToken, int $page = 1, int $per_page = 50): array
    {
        $resp = Http::withToken($accessToken)
                   ->get("https://public-api.wordpress.com/rest/v1.1/sites/{$this->siteId}/posts", [
                       'page' => $page,
                       'number' => $per_page
                   ]);
        if (! $resp->successful()) return [];
        $json = $resp->json();
        return $json['posts'] ?? [];
    }

    public function createPost(string $accessToken, array $data): array
    {
        $resp = Http::withToken($accessToken)
               ->post("https://public-api.wordpress.com/rest/v1.1/sites/{$this->siteId}/posts/new", $data);
        return $resp->successful() ? $resp->json() : [];
    }

    public function updatePost(string $accessToken, string $wpId, array $data): array
    {
        $resp = Http::withToken($accessToken)
               ->post("https://public-api.wordpress.com/rest/v1.1/sites/{$this->siteId}/posts/{$wpId}/", $data);
        return $resp->successful() ? $resp->json() : [];
    }

    public function deletePost(string $accessToken, string $wpId): bool
    {
        $resp = Http::withToken($accessToken)
               ->delete("https://public-api.wordpress.com/rest/v1.1/sites/{$this->siteId}/posts/{$wpId}/delete");
        return $resp->successful();
    }
}
