<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'supplier_product_id',
        'name',
        'selling_price',
        'is_auto_price',
        'margin',
        'is_active',
        'description',
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'margin' => 'decimal:2',
        'is_auto_price' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the game that owns this product
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the supplier product
     */
    public function supplierProduct(): BelongsTo
    {
        return $this->belongsTo(SupplierProduct::class);
    }

    /**
     * Get all orders for this product
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all FlazzSell events that include this product
     */
    public function flazzSells(): BelongsToMany
    {
        return $this->belongsToMany(FlazzSell::class, 'flazz_sell_products')
            ->withPivot(['discount_percentage', 'quota', 'sold_count'])
            ->withTimestamps();
    }

    /**
     * Get the active FlazzSell for this product (from any active event)
     */
    public function activeFlazzSellProduct(): ?FlazzSellProduct
    {
        return FlazzSellProduct::where('product_id', $this->id)
            ->whereHas('flazzSell', function ($query) {
                $query->where('is_active', true)
                    ->where('start_at', '<=', now())
                    ->where('end_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('quota')
                    ->orWhereRaw('sold_count < quota');
            })
            ->first();
    }

    /**
     * Get the current price (discounted if in active FlazzSell, otherwise regular price)
     */
    public function getCurrentPrice(): float
    {
        $flazzSellProduct = $this->activeFlazzSellProduct();

        if ($flazzSellProduct) {
            return $flazzSellProduct->getDiscountedPrice();
        }

        return $this->selling_price;
    }

    /**
     * Check if product is in an active FlazzSell event
     */
    public function hasActiveFlazzSell(): bool
    {
        return $this->activeFlazzSellProduct() !== null;
    }
}
