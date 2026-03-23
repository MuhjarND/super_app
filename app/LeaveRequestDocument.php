<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveRequestDocument extends Model
{
    protected $fillable = ['leave_request_id','document_type','original_name','file_path','mime_type','file_size','is_verified','verified_by','verified_at','verification_note'];
    protected $casts = ['is_verified' => 'boolean','file_size' => 'integer','verified_at' => 'datetime'];
    public function leaveRequest() { return $this->belongsTo(LeaveRequest::class); }
    public function verifier() { return $this->belongsTo(User::class, 'verified_by'); }

    public function getDocumentTypeLabelAttribute()
    {
        $map = [
            'surat_dokter' => 'Surat Dokter',
            'dokumen_pendukung' => 'Dokumen Pendukung',
            'dokumen_alasan_penting' => 'Dokumen Alasan Penting',
        ];

        return $map[$this->document_type] ?? ucfirst(str_replace('_', ' ', (string) $this->document_type));
    }
}
