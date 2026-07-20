<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModuleSetting extends Model
{
    const STATUS_PUBLISHED = 'published';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'module_key',
        'status',
        'custom_label',
        'maintenance_message',
        'show_desktop',
        'show_mobile',
        'display_order',
        'settings',
        'updated_by',
    ];

    protected $casts = [
        'show_desktop' => 'boolean',
        'show_mobile' => 'boolean',
        'display_order' => 'integer',
        'settings' => 'array',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
