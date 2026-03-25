<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuratTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'status',
        'field_schema',
        'template_body',
        'sample_file_path',
        'source_type',
        'source_request_id',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'field_schema' => 'array',
        'approved_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sourceRequest()
    {
        return $this->belongsTo(SuratTemplateProposal::class, 'source_request_id');
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'active' => 'Aktif',
            'inactive' => 'Nonaktif',
        ];

        return $labels[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'draft' => 'warning',
            'active' => 'success',
            'inactive' => 'secondary',
        ];

        return $classes[$this->status] ?? 'secondary';
    }
}

