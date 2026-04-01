<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiReview extends Model
{
    protected $fillable = ['reviewable_type', 'reviewable_id', 'review_scope', 'status', 'review_notes', 'reviewed_by', 'reviewed_at'];
    protected $casts = ['reviewed_at' => 'datetime'];

    public function reviewable() { return $this->morphTo(); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function getStatusBadgeAttribute()
    {
        $map = ['approved' => ['success', 'Approved'], 'revisi' => ['warning', 'Revisi'], 'rejected' => ['danger', 'Rejected']];
        [$class, $label] = $map[$this->status] ?? ['secondary', ucfirst((string) $this->status)];
        return '<span class="badge badge-' . $class . ' app-status-badge">' . $label . '</span>';
    }
}
