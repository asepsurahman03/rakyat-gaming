<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'user_id',
        'game_id',
        'product_id',
        'email',
        'whatsapp',
        'data_player',
        'payment_method',
        'price',
        'status',
        'note',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'data_player' => 'array',
    ];

    /**
     * Get the user that owns this order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the game for this order
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the product for this order
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the payment for this order
     */
    public function orderPayment(): HasOne
    {
        return $this->hasOne(OrderPayment::class);
    }

    /**
     * Get the payment gateway through order payment
     */
    public function paymentGateway(): HasOneThrough
    {
        return $this->hasOneThrough(
            PaymentGateway::class,
            OrderPayment::class,
            'order_id', // Foreign key on order_payments table
            'id', // Foreign key on payment_gateways table
            'id', // Local key on orders table
            'payment_gateway_id' // Local key on order_payments table
        );
    }

    /**
     * Get all digiflazz logs for this order
     */
    public function digiflazzLogs(): HasMany
    {
        return $this->hasMany(DigiflazzLog::class);
    }
}
