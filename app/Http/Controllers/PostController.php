<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Services\WordPressService;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    protected WordPressService $wp;

    public function __construct(WordPressService $wp)
    {
        $this->middleware('auth');
        $this->wp = $wp;
    }

    public function index(Request $req)
    {
        $query = Post::query();
        if ($req->get('sort') === 'priority') {
            $query->orderBy('priority','desc');
        } else {
            $query->orderBy('updated_at','desc');
        }
        $posts = $query->get();
        if ($posts->isEmpty()) {
            // initial sync from WP
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
            'status' => $req->status ?? 'draft',
        ]);
        $local = Post::create([
            'wordpress_id' => $wpPost['ID'] ?? null,
            'title' => $wpPost['title'] ?? $req->title,
            'content' => $wpPost['content'] ?? $req->content,
            'status' => $wpPost['status'] ?? ($req->status ?? 'draft'),
            'wp_updated_at' => $wpPost['date'] ?? now()
        ]);
        return response()->json($local, 201);
    }

    public function update(Request $req, Post $post)
    {
        $req->validate(['title'=>'required','content'=>'required']);
        $token = Auth::user()->access_token;
        $wpRes = $this->wp->updatePost($token, $post->wordpress_id, [
            'title' => $req->title,
            'content' => $req->content,
            'status' => $req->status ?? $post->status,
        ]);
        $post->update([
            'title' => $wpRes['title'] ?? $req->title,
            'content' => $wpRes['content'] ?? $req->content,
            'status' => $wpRes['status'] ?? ($req->status ?? $post->status),
            'wp_updated_at' => $wpRes['modified'] ?? now()
        ]);
        return response()->json($post);
    }

    public function destroy(Post $post)
    {
        $token = Auth::user()->access_token;
        if ($post->wordpress_id) {
            $this->wp->deletePost($token, $post->wordpress_id);
        }
        $post->delete();
        return response()->json(['deleted'=>true]);
    }

    public function setPriority(Request $req, Post $post)
    {
        $req->validate(['priority'=>'required|integer']);
        $post->update(['priority' => (int)$req->priority]);
        return response()->json($post);
    }
}
