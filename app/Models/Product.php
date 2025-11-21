<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
