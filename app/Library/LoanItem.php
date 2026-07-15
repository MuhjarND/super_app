<?php

namespace App\Library;

use Illuminate\Database\Eloquent\Model;

class LoanItem extends Model
{
    protected $table = 'library_loan_items';
    protected $fillable = ['loan_id', 'book_copy_id', 'returned_at', 'condition'];

    protected $casts = [
        'returned_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function bookCopy()
    {
        return $this->belongsTo(BookCopy::class);
    }

    public function fine()
    {
        return $this->hasOne(Fine::class);
    }

    public function isReturned()
    {
        return !is_null($this->returned_at);
    }
}
