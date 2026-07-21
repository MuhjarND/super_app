<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuratKeluarCalendarEvent extends Model
{
    const TYPES = [
        'rapat' => ['label' => 'Rapat', 'color' => '#2563eb'],
        'agenda_pimpinan' => ['label' => 'Agenda Pimpinan', 'color' => '#64748b'],
        'virtual_meeting' => ['label' => 'Virtual Meeting', 'color' => '#0891b2'],
        'cuti' => ['label' => 'Cuti', 'color' => '#dc2626'],
        'zi' => ['label' => 'Progress ZI', 'color' => '#d97706'],
        'surat_tugas' => ['label' => 'Surat Tugas', 'color' => '#16a34a'],
    ];

    protected $fillable = [
        'surat_keluar_id',
        'type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $dates = ['start_date', 'end_date'];

    public function suratKeluar()
    {
        return $this->belongsTo(SuratKeluar::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function typeOptions()
    {
        return static::TYPES;
    }

    public function getTypeLabelAttribute()
    {
        return static::TYPES[$this->type]['label'] ?? 'Agenda';
    }

    public function getTypeColorAttribute()
    {
        return static::TYPES[$this->type]['color'] ?? '#4f46e5';
    }
}
