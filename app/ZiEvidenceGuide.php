<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiEvidenceGuide extends Model
{
    protected $table = 'zi_evidence_guides';
    protected $primaryKey = 'guide_key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['source_row' => 'integer', 'supporting_data' => 'array', 'images' => 'array'];
}
