<?php

namespace App\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $table = 'library_categories';
    protected $fillable = ['name', 'slug', 'description'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
        static::updating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
