<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class WebhookController extends Controller
{
    public function receive(Request $request)
    {
        // verify signature if WP provides one (recommended)
        // payload example includes event type and post data
        $payload = $request->all();

        // Example: a new post created on WP
        if (isset($payload['event']) && $payload['event'] === 'post.created') {
            $wp = $payload['post'];
            Post::updateOrCreate(
                ['wordpress_id' => $wp['ID']],
                [
                    'title' => $wp['title'],
                    'content' => $wp['content'],
                    'status' => $wp['status'],
                    'wp_updated_at' => $wp['date']
                ]
            );
        }
        // Handle post.updated, post.deleted similarly
        return response()->json(['ok'=>true]);
    }
}
