<?php

namespace App\Library;

use App\User;

use Illuminate\Database\Eloquent\Model;

class ReturnModel extends Model
{
    protected $table = 'library_returns';

    protected $fillable = ['loan_id', 'user_id', 'return_date', 'note'];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
