<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WordPressService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected WordPressService $wp;

    public function __construct(WordPressService $wp)
    {
        $this->wp = $wp;
    }

    public function redirectToProvider()
    {
        return redirect($this->wp->getAuthUrl());
    }

    public function handleProviderCallback(Request $request)
    {
        $code = $request->query('code');
        if (! $code) {
            return redirect('/')->withErrors('Authorization code missing.');
        }

        $tokenData = $this->wp->exchangeCodeForToken($code);
        $accessToken = $tokenData['access_token'] ?? null;
        if (! $accessToken) {
            return redirect('/')->withErrors('Unable to obtain access token from WordPress.');
        }

        $sites = $this->wp->getUserSites($accessToken);
        $allowed = false;
        foreach ($sites as $site) {
            $siteId = $site['ID'] ?? ($site['ID'] ?? null);
            $siteUrl = $site['URL'] ?? ($site['url'] ?? null);
            if ($siteId == config('services.wordpress.site_id') || $siteUrl == config('services.wordpress.site_id')) {
                if (!empty($site['roles']) && in_array('administrator', (array)$site['roles'])) {
                    $allowed = true;
                    break;
                }
            }
        }

        if (! $allowed) {
            return redirect('/')->withErrors('You must be an administrator of the configured WordPress site.');
        }

        $me = $this->wp->getMe($accessToken);
        $email = $me['email'] ?? null;
        $name = $me['display_name'] ?? ($me['username'] ?? 'WP User');
        $wordpressId = $me['ID'] ?? null;

        $user = User::updateOrCreate(
            ['email' => $email ?: "wpuser_{$wordpressId}@example.invalid"],
            [
                'name' => $name,
                'wordpress_id' => $wordpressId,
                'access_token' => $accessToken,
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
            ]
        );

        Auth::login($user, true);
        return redirect('/app');
    }
}
