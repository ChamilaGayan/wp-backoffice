<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class WebhookController extends Controller
{
    public function receive(Request $request)
    {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $post = $payload['post'] ?? null;

        if ($event && $post) {
            switch ($event) {
                case 'post.created':
                case 'post.updated':
                    Post::updateOrCreate(
                        ['wordpress_id' => $post['ID']],
                        [
                            'title' => $post['title'] ?? '',
                            'content' => $post['content'] ?? '',
                            'status' => $post['status'] ?? 'draft',
                            'wp_updated_at' => $post['date'] ?? null
                        ]
                    );
                    break;
                case 'post.deleted':
                    Post::where('wordpress_id', $post['ID'])->delete();
                    break;
            }
        }

        return response()->json(['ok' => true]);
    }
}
