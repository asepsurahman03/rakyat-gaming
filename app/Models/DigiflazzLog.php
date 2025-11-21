<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigiflazzLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'request_body',
        'response_body',
        'status',
    ];

    protected $casts = [
        'request_body' => 'array',
        'response_body' => 'array',
    ];

    /**
     * Get the order that owns this log
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
