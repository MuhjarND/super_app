<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PdfVerification extends Model
{
    protected $fillable = [
        'token',
        'module',
        'document_type',
        'document_id',
        'title',
        'file_path',
        'original_filename',
        'file_hash',
        'file_size',
        'signers',
        'metadata',
        'created_by',
        'finalized_at',
    ];

    protected $casts = [
        'signers' => 'array',
        'metadata' => 'array',
        'file_size' => 'integer',
        'finalized_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
