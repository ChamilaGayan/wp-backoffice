<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Services\WordPressService;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    protected $wp;
    public function __construct(WordPressService $wp) { $this->wp = $wp; }

    // List local posts (if empty, sync from WP)
    public function index(Request $req)
    {
        // allow sorting by priority
        $q = Post::query();
        if ($req->get('sort') == 'priority') {
            $q->orderBy('priority','desc');
        } else {
            $q->orderBy('updated_at','desc');
        }
        $posts = $q->get();
        if ($posts->isEmpty()) {
            // attempt initial sync
            $token = Auth::user()->access_token;
            $wpPosts = $this->wp->listPosts($token,1,100);
            foreach ($wpPosts as $wp) {
                Post::updateOrCreate(
                    ['wordpress_id' => $wp['ID'] ?? $wp['ID']],
                    [
                        'title' => $wp['title'] ?? '',
                        'content' => $wp['content'] ?? '',
                        'status' => $wp['status'] ?? 'draft',
                        'wp_updated_at' => $wp['date'] ?? null
                    ]
                );
            }
            $posts = Post::all();
        }
        return response()->json($posts);
    }

    public function store(Request $req)
    {
        $req->validate(['title'=>'required','content'=>'required']);
        $token = Auth::user()->access_token;
        $wpPost = $this->wp->createPost($token, [
            'title' => $req->title,
            'content' => $req->content,
            'status' => $req->status ?? 'draft'
        ]);
        $local = Post::create([
            'wordpress_id' => $wpPost['ID'] ?? null,
            'title' => $wpPost['title'] ?? $req->title,
            'content' => $wpPost['content'] ?? $req->content,
            'status' => $wpPost['status'] ?? ($req->status ?? 'draft')
        ]);
        return response()->json($local,201);
    }

    public function update(Request $req, Post $post)
    {
        $token = Auth::user()->access_token;
        $wpRes = $this->wp->updatePost($token, $post->wordpress_id, [
            'title' => $req->title,
            'content' => $req->content,
            'status' => $req->status,
        ]);
        $post->update([
            'title' => $wpRes['title'] ?? $req->title,
            'content' => $wpRes['content'] ?? $req->content,
            'status' => $wpRes['status'] ?? $req->status,
            'wp_updated_at' => $wpRes['modified'] ?? now()
        ]);
        return response()->json($post);
    }

    public function destroy(Post $post)
    {
        $token = Auth::user()->access_token;
        $this->wp->deletePost($token, $post->wordpress_id);
        $post->delete();
        return response()->json(['deleted'=>true]);
    }

    public function setPriority(Request $req, Post $post)
    {
        $post->update(['priority' => (int)$req->priority]);
        return response()->json($post);
    }
}
