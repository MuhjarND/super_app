<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryTransactionAttachment extends Model
{
    protected $fillable = [
        'inventory_maintenance_transaction_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function transaction()
    {
        return $this->belongsTo(InventoryMaintenanceTransaction::class, 'inventory_maintenance_transaction_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
