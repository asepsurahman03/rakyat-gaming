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
        'options',
        'is_required',
        'description',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'options' => 'array',
    ];

    /**
     * Get the game that owns this field form
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
