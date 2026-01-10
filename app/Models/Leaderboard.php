<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'user_id',
        'name',
        'total_spent',
        'transaction_count',
        'last_item',
        'last_transaction_time',
        'is_active',
        'rank',
    ];

    protected $casts = [
        'total_spent' => 'decimal:2',
        'transaction_count' => 'integer',
        'last_transaction_time' => 'datetime',
        'is_active' => 'boolean',
        'rank' => 'integer',
    ];

    /**
     * Scope untuk filter berdasarkan periode
     */
    public function scopePeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope untuk data yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Relasi ke User (opsional, jika ingin menghubungkan dengan user asli)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
