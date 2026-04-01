<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiActivityRealization extends Model
{
    protected $fillable = ['zi_activity_id', 'realization_date', 'implementation_summary', 'result_summary', 'obstacles', 'follow_up', 'source_type', 'source_reference_type', 'source_reference_id', 'created_by', 'updated_by'];
    protected $casts = ['realization_date' => 'date'];

    public function activity() { return $this->belongsTo(ZiActivity::class, 'zi_activity_id'); }
    public function evidences() { return $this->hasMany(ZiEvidence::class, 'zi_activity_realization_id')->latest(); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function getSourceLabelAttribute() { $map = ['manual' => 'Manual', 'persuratan' => 'Persuratan', 'rapat' => 'Rapat', 'cuti' => 'Cuti']; return $map[$this->source_type] ?? ucfirst((string) $this->source_type); }
}
