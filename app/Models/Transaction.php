<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'payment_gateway',
        'type',
        'amount',
        'currency',
        'credits',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credits' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a purchase transaction
     */
    public static function recordPurchase(int $userId, float $credits, float $amount, string $gateway, string $transactionId): self
    {
        return self::create([
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'payment_gateway' => $gateway,
            'type' => 'purchase',
            'amount' => $amount,
            'currency' => 'USD',
            'credits' => $credits,
            'status' => 'completed',
            'metadata' => 'Credit purchase',
        ]);
    }

    /**
     * Create a usage transaction
     */
    public static function recordUsage(int $userId, float $credits, float $actualCost, string $description): self
    {
        return self::create([
            'user_id' => $userId,
            'transaction_id' => 'usage_' . uniqid(),
            'payment_gateway' => 'internal',
            'type' => 'usage',
            'amount' => $actualCost,
            'currency' => 'USD',
            'credits' => $credits,
            'status' => 'completed',
            'metadata' => $description,
        ]);
    }
}
