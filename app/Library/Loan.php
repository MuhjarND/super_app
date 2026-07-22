<?php

namespace App\Library;

use App\User;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    protected $table = 'library_loans';
    protected $fillable = [
        'loan_number', 'member_id', 'user_id',
        'loan_date', 'due_date', 'status', 'note'
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date'  => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loanItems()
    {
        return $this->hasMany(LoanItem::class);
    }

    public function returnRecord()
    {
        return $this->hasOne(ReturnModel::class);
    }

    public function isOverdue()
    {
        return $this->status !== 'dikembalikan' && Carbon::now()->greaterThan($this->due_date);
    }

    public function getDaysLateAttribute()
    {
        if ($this->status === 'dikembalikan' && $this->returnRecord) {
            $returnDate = Carbon::parse($this->returnRecord->return_date);
            $dueDate = Carbon::parse($this->due_date);
            return $returnDate->greaterThan($dueDate) ? $dueDate->diffInDays($returnDate) : 0;
        }
        if ($this->isOverdue()) {
            return Carbon::now()->startOfDay()->diffInDays(Carbon::parse($this->due_date)->startOfDay());
        }
        return 0;
    }

    public static function generateNumber()
    {
        $prefix = 'PM-' . date('Ymd') . '-';
        $lastLoan = self::where('loan_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLoan) {
            $lastNum = (int) substr($lastLoan->loan_number, -4);
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }

        return $prefix . $newNum;
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'dipinjam'     => 'warning',
            'dikembalikan' => 'success',
            'terlambat'    => 'danger',
        ];
        return $badges[$this->status] ?? 'secondary';
    }

    public function isVisibleTo(User $user)
    {
        if ($user->canManageLibraryModule()) {
            return true;
        }

        $member = $this->relationLoaded('member') ? $this->member : $this->member()->first();

        return $member && (int) $member->user_id === (int) $user->id;
    }
}
