<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    protected $fillable = [
        'supplier_code',
        'payload',
        'signature',
        'ip_address',
        'response',
        'status',
        'is_valid',
        'order_id',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the supplier for this webhook
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_code', 'code');
    }

    /**
     * Get the order associated with this webhook
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    /**
     * Scope for valid webhooks
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope for invalid webhooks
     */
    public function scopeInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    /**
     * Scope for specific supplier
     */
    public function scopeForSupplier($query, string $supplierCode)
    {
        return $query->where('supplier_code', $supplierCode);
    }

    /**
     * Scope for pending webhooks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for processed webhooks
     */
    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }
}
