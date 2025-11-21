<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'code',
        'category',
        'brand',
        'product_name',
        'buyer_price',
        'status',
        'raw_json',
        'last_sync_at',
    ];

    protected $casts = [
        'buyer_price' => 'decimal:2',
        'raw_json' => 'array',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get the supplier that owns this product
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get all products using this supplier product
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
