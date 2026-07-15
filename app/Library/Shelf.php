<?php

namespace App\Library;

use Illuminate\Database\Eloquent\Model;

class Shelf extends Model
{
    protected $table = 'library_shelves';
    protected $fillable = ['code', 'name', 'location', 'description'];

    public function books()
    {
        return $this->hasMany(Book::class);
    }
}
