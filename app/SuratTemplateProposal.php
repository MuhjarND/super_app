<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuratTemplateProposal extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'requested_fields',
        'suggested_template_body',
        'example_file_path',
        'status',
        'review_notes',
        'requested_by',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'requested_fields' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'submitted' => 'Diajukan',
            'in_review' => 'Ditinjau',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        return $labels[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeClassAttribute()
    {
        $classes = [
            'submitted' => 'warning',
            'in_review' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
        ];

        return $classes[$this->status] ?? 'secondary';
    }
}

