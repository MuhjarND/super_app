<?php

namespace App\Library;

use App\User;

use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    protected $table = 'library_fines';
    protected $fillable = [
        'loan_item_id', 'member_id', 'days_late',
        'amount_per_day', 'total_amount', 'status', 'paid_at', 'paid_by'
    ];

    protected $casts = [
        'paid_at'       => 'datetime',
        'amount_per_day' => 'decimal:2',
        'total_amount'   => 'decimal:2',
    ];

    public function loanItem()
    {
        return $this->belongsTo(LoanItem::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function getStatusBadgeAttribute()
    {
        return $this->status === 'lunas' ? 'success' : 'danger';
    }
}
