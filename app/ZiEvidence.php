<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiEvidence extends Model
{
    protected $table = 'zi_evidences';
    protected $fillable = ['zi_activity_realization_id', 'title', 'description', 'evidence_type', 'source_type', 'source_reference_type', 'source_reference_id', 'file_path', 'file_name', 'mime_type', 'file_size', 'status', 'is_auto_linked', 'uploaded_by'];
    protected $casts = ['is_auto_linked' => 'boolean', 'file_size' => 'integer'];

    public function realization() { return $this->belongsTo(ZiActivityRealization::class, 'zi_activity_realization_id'); }
    public function indicators() { return $this->belongsToMany(ZiIndicator::class, 'zi_indicator_evidence')->withPivot('notes')->withTimestamps(); }
    public function reviews() { return $this->morphMany(ZiReview::class, 'reviewable')->latest('reviewed_at'); }
    public function latestReview() { return $this->morphOne(ZiReview::class, 'reviewable')->latest('reviewed_at'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getStatusLabelAttribute()
    {
        $map = ['belum_ada' => 'Belum Ada', 'terupload' => 'Terupload', 'terhubung' => 'Terhubung', 'valid' => 'Valid', 'revisi' => 'Revisi', 'tidak_valid' => 'Tidak Valid'];
        return $map[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusBadgeAttribute()
    {
        $map = ['belum_ada' => ['secondary', 'Belum Ada'], 'terupload' => ['info', 'Terupload'], 'terhubung' => ['primary', 'Terhubung'], 'valid' => ['success', 'Valid'], 'revisi' => ['warning', 'Revisi'], 'tidak_valid' => ['danger', 'Tidak Valid']];
        [$class, $label] = $map[$this->status] ?? ['secondary', $this->status_label];
        return '<span class="badge badge-' . $class . ' app-status-badge">' . $label . '</span>';
    }

    public function getSourceReferenceLabelAttribute()
    {
        $map = ['manual' => 'Upload Manual', 'surat_masuk' => 'Surat Masuk', 'surat_keluar' => 'Surat Keluar', 'disposisi' => 'Disposisi', 'rapat' => 'Undangan Rapat', 'rapat_notulensi' => 'Notulensi', 'rapat_laporan' => 'Laporan Tindak Lanjut', 'leave_request' => 'Cuti'];
        return $map[$this->source_reference_type ?: 'manual'] ?? ucfirst((string) $this->source_reference_type);
    }

    public function getPreviewUrlAttribute()
    {
        if ($this->file_path) { return route('progress-zi.evidences.file', $this); }
        switch ($this->source_reference_type) {
            case 'surat_masuk': return route('surat-masuk.preview', $this->source_reference_id);
            case 'surat_keluar': return route('surat-keluar.file', $this->source_reference_id);
            case 'disposisi': return route('surat-masuk.index');
            case 'rapat': return route('rapat.undangan.preview', $this->source_reference_id);
            case 'rapat_notulensi': return route('rapat.notulensi.pdf', $this->source_reference_id);
            case 'rapat_laporan': return route('rapat.laporan.preview', $this->source_reference_id);
            case 'leave_request': return route('cuti.surat', $this->source_reference_id);
            default: return null;
        }
    }
}
