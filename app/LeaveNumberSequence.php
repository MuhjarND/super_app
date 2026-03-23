<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveNumberSequence extends Model
{
    protected $fillable = ['year','sequence_type','prefix','last_number','meta_json'];
    protected $casts = ['year' => 'integer','last_number' => 'integer','meta_json' => 'array'];
}
