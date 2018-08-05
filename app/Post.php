<?php

namespace App;
use App\User;
use App\Comment;
use App\Category;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'description',
        'author',
        'category',
        'color',
        'image',
        'is_publish'
    ];
    protected $with = ['creator', 'comments', 'category'];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if(is_null($post->author)) {
                $post->author = auth()->user()->id;
            }
        });

        static::deleting(function ($post) {
            $post->comments()->delete();
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'author');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeDrafted($query)
    {
        return $query->where('is_published', false);
    }

    public function getPublishedAttribute()
    {
        return ($this->is_published) ? 'Yes' : 'No';
    }
}
