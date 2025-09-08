<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Post extends Model {
    protected $fillable = [
        'wordpress_id','title','content','status','priority','wp_updated_at'
    ];

    protected $casts = [
        'wp_updated_at' => 'datetime',
    ];
}
