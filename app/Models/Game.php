<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'image',
        'banner',
        'description',
        'provider',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the category this game belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all products for this game
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all orders for this game
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function fieldForms(): HasMany
    {
        return $this->hasMany(FieldForm::class);
    }

    /**
     * Get all posts for this game
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
