<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class FlazzSell extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'banner',
        'start_at',
        'end_at',
        'is_active',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get all products in this FlazzSell event
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flazz_sell_products')
            ->withPivot(['discount_percentage', 'quota', 'sold_count'])
            ->withTimestamps();
    }

    /**
     * Get FlazzSellProduct records
     */
    public function flazzSellProducts(): HasMany
    {
        return $this->hasMany(FlazzSellProduct::class);
    }

    /**
     * Scope to get only active events
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now());
    }

    /**
     * Check if this event is currently active
     */
    public function isActive(): bool
    {
        return $this->is_active
            && $this->start_at <= now()
            && $this->end_at >= now();
    }

    /**
     * Get time remaining for this event
     */
    public function getTimeRemaining(): array
    {
        if (!$this->isActive()) {
            return ['days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0];
        }

        $now = Carbon::now();
        $end = Carbon::parse($this->end_at);

        $diff = $now->diff($end);

        return [
            'days' => $diff->days,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'seconds' => $diff->s,
        ];
    }

    /**
     * Get active products in this event (with quota available)
     */
    public function getActiveProducts()
    {
        if (!$this->isActive()) {
            return collect();
        }

        return $this->flazzSellProducts()
            ->with('product')
            ->get()
            ->filter(function ($flazzSellProduct) {
                // Check if quota is available
                if ($flazzSellProduct->quota !== null) {
                    return $flazzSellProduct->sold_count < $flazzSellProduct->quota;
                }
                return true;
            });
    }
}
