<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WordPressService
{
    protected $clientId;
    protected $clientSecret;
    protected $redirect;
    protected $siteId;

    public function __construct()
    {
        $this->clientId = config('services.wordpress.client_id');
        $this->clientSecret = config('services.wordpress.client_secret');
        $this->redirect = config('services.wordpress.redirect');
        $this->siteId = config('services.wordpress.site_id');
    }

    // URL to redirect user for authorization
    public function getAuthUrl($state = null)
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirect,
            'response_type' => 'code',
            'scope' => 'global', // or narrower scopes if desired
            'state' => $state ?? Str::random(40),
        ]);
        return "https://public-api.wordpress.com/oauth2/authorize?{$params}";
    }

    // Exchange code for access token
    public function exchangeCodeForToken(string $code)
    {
        $resp = Http::asForm()->post('https://public-api.wordpress.com/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirect,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);
        return $resp->json();
    }

    // Get current user's sites
    public function getUserSites(string $accessToken)
    {
        $resp = Http::withToken($accessToken)->get('https://public-api.wordpress.com/rest/v1.1/me/sites');
        return $resp->successful() ? $resp->json() : null;
    }

    // List posts (simple)
    public function listPosts(string $accessToken, $page = 1, $per_page = 20)
    {
        // using v1.1 posts endpoint
        $resp = Http::withToken($accessToken)
                   ->get("https://public-api.wordpress.com/rest/v1.1/sites/{$this->siteId}/posts", [
                       'page' => $page,
                       'number' => $per_page
                   ]);
        return $resp->successful() ? $resp->json()['posts'] ?? [] : [];
    }

    public function createPost(string $accessToken, array $data)
    {
        $resp = Http::withToken($accessToken)
               ->post("https://public-api.wordpress.com/rest/v1.1/sites/{$this->siteId}/posts/new", $data);
        return $resp->json();
    }

    public function updatePost(string $accessToken, string $wpId, array $data)
    {
        $resp = Http::withToken($accessToken)
               ->post("https://public-api.wordpress.com/rest/v1.1/sites/{$this->siteId}/posts/{$wpId}/", $data);
        return $resp->json();
    }

    public function deletePost(string $accessToken, string $wpId)
    {
        $resp = Http::withToken($accessToken)
               ->delete("https://public-api.wordpress.com/rest/v1.1/sites/{$this->siteId}/posts/{$wpId}/delete");
        return $resp->successful();
    }
}
