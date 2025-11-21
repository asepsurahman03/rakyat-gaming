<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'label',
        'name',
        'type',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    /**
     * Get the game that owns this field form
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
