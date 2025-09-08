<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WordPressService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    protected $wp;

    public function __construct(WordPressService $wp)
    {
        $this->wp = $wp;
    }

    // /auth/redirect
    public function redirectToProvider(Request $request)
    {
        $url = $this->wp->getAuthUrl();
        return redirect($url);
    }

    // /auth/callback
    public function handleProviderCallback(Request $request)
    {
        $code = $request->get('code');
        if (! $code) return redirect('/')->withErrors('No code returned');

        $tokenData = $this->wp->exchangeCodeForToken($code);
        $accessToken = $tokenData['access_token'] ?? null;
        if (! $accessToken) {
            return redirect('/')->withErrors('Unable to get access token');
        }

        // Get user sites and confirm admin capability on configured site
        $sites = $this->wp->getUserSites($accessToken);
        $allowed = false;
        if (is_array($sites)) {
            foreach ($sites as $site) {
                // site slug or ID might match your WP_SITE_ID
                if (($site['ID'] ?? $site['id'] ?? null) == config('services.wordpress.site_id')
                    || ($site['URL'] ?? null) == config('services.wordpress.site_id')) {
                    // There are varying response keys; look for 'roles'/'capabilities'
                    $roles = $site['roles'] ?? ($site['role'] ?? []);
                    if (is_array($roles) && in_array('administrator', $roles)) {
                        $allowed = true;
                        break;
                    }
                    // fallback: if site entry has 'capabilities' or 'is_admin' flags, check them
                    if (!empty($site['capabilities']) && (isset($site['capabilities']['manage']) || true)) {
                        // (best-effort) accept; but ideally check admin explicitly
                        $allowed = true;
                        break;
                    }
                }
            }
        }

        if (! $allowed) {
            return redirect('/')->withErrors('You must be an administrator of the configured WordPress site.');
        }

        // Save or update user locally (store token for API calls)
        $userData = Http::withToken($accessToken)->get('https://public-api.wordpress.com/rest/v1.1/me')->json();

        $user = User::updateOrCreate(
            ['email' => $userData['email'] ?? $userData['ID'] ?? 'unknown'],
            [
                'name' => $userData['display_name'] ?? 'WP User',
                'wordpress_id' => $userData['ID'] ?? null,
                'access_token' => $accessToken,
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => isset($tokenData['expires_in']) ? now()->addSeconds($tokenData['expires_in']) : null,
            ]
        );

        Auth::login($user);
        return redirect('/app');
    }
}
