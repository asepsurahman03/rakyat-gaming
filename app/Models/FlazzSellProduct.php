<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlazzSellProduct extends Model
{
    protected $fillable = [
        'flazz_sell_id',
        'product_id',
        'discount_percentage',
        'quota',
        'sold_count',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'quota' => 'integer',
        'sold_count' => 'integer',
    ];

    /**
     * Get the FlazzSell event
     */
    public function flazzSell(): BelongsTo
    {
        return $this->belongsTo(FlazzSell::class);
    }

    /**
     * Get the Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if this product is available (quota not reached)
     */
    public function isAvailable(): bool
    {
        if ($this->quota === null) {
            return true;
        }
        return $this->sold_count < $this->quota;
    }

    /**
     * Get discounted price for this product
     */
    public function getDiscountedPrice(): float
    {
        $product = $this->product;
        $discount = ($product->selling_price * $this->discount_percentage) / 100;
        return $product->selling_price - $discount;
    }

    /**
     * Get savings amount
     */
    public function getSavings(): float
    {
        $product = $this->product;
        return ($product->selling_price * $this->discount_percentage) / 100;
    }

    /**
     * Increment sold count
     */
    public function incrementSoldCount(): void
    {
        $this->increment('sold_count');
    }
}
