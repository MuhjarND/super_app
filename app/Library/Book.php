<?php

namespace App\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    protected $table = 'library_books';
    protected $fillable = [
        'title', 'author', 'publisher', 'year', 'isbn',
        'category_id', 'shelf_id', 'description', 'cover', 'stock'
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Book $book) {
            if (!$book->isForceDeleting()) {
                $book->copies()->delete();
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function shelf()
    {
        return $this->belongsTo(Shelf::class);
    }

    public function copies()
    {
        return $this->hasMany(BookCopy::class);
    }

    public function availableCopies()
    {
        return $this->hasMany(BookCopy::class)->where('status', 'tersedia');
    }

    public function getCoverUrlAttribute()
    {
        if ($this->cover) {
            return asset('storage/' . $this->cover);
        }
        return asset('images/library-no-cover.png');
    }

    public function getAvailableCountAttribute()
    {
        return $this->copies()->where('status', 'tersedia')->count();
    }

    public function getCopiesCountAttribute()
    {
        return $this->copies()->count();
    }
}
