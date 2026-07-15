<?php

namespace App\Library;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'library_members';
    protected $fillable = [
        'member_number', 'name', 'gender', 'class_position',
        'address', 'phone', 'email', 'photo', 'status', 'valid_until'
    ];

    protected $casts = [
        'valid_until' => 'date',
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function fines()
    {
        return $this->hasMany(Fine::class);
    }

    public function activeLoans()
    {
        return $this->hasMany(Loan::class)->whereIn('status', ['dipinjam', 'terlambat']);
    }

    public function unpaidFines()
    {
        return $this->hasMany(Fine::class)->where('status', 'belum_dibayar');
    }

    public static function generateNumber()
    {
        $year = date('Y');
        $lastMember = self::where('member_number', 'like', "AG-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMember) {
            $lastNum = (int) substr($lastMember->member_number, -4);
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }

        return "AG-{$year}-{$newNum}";
    }

    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }
        return asset('images/library-no-avatar.png');
    }

    public function getActiveLoansCountAttribute()
    {
        return $this->loans()->whereIn('status', ['dipinjam', 'terlambat'])->count();
    }

    public function getUnpaidFinesCountAttribute()
    {
        return $this->fines()->where('status', 'belum_dibayar')->count();
    }

    public function getLoansCountAttribute()
    {
        return $this->loans()->count();
    }
}
