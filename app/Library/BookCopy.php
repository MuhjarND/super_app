<?php

namespace App\Library;

use Illuminate\Database\Eloquent\Model;

class BookCopy extends Model
{
    protected $table = 'library_book_copies';
    protected $fillable = ['copy_code', 'book_id', 'status', 'notes'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function loanItems()
    {
        return $this->hasMany(LoanItem::class);
    }

    public function activeLoanItem()
    {
        return $this->hasOne(LoanItem::class)->whereNull('returned_at');
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'tersedia' => 'success',
            'dipinjam' => 'warning',
            'rusak'    => 'danger',
            'hilang'   => 'dark',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    public static function generateCode()
    {
        $year = date('Y');
        $lastCopy = self::where('copy_code', 'like', "BK-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCopy) {
            $lastNum = (int) substr($lastCopy->copy_code, -6);
            $newNum = str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNum = '000001';
        }

        return "BK-{$year}-{$newNum}";
    }
}
