<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'api_key',
        'api_secret',
        'callback_url',
        'redirect_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    /**
     * Get all order payments for this gateway
     */
    public function orderPayments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }
}
