<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DisposisiDokumentasi extends Model
{
    protected $fillable = [
        'disposisi_id',
        'uploaded_by',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function disposisi()
    {
        return $this->belongsTo(Disposisi::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFormattedSizeAttribute()
    {
        $bytes = (int) $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }

        return number_format(max($bytes, 1) / 1024, 0, ',', '.') . ' KB';
    }
}
