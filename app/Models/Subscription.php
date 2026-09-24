<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    public const PROVIDER_RAZORPAY = 'razorpay';

    public const PROVIDER_PAYPAL = 'paypal';

    public const PROVIDER_STRIPE = 'stripe';

    public const STATUS_ACTIVE = 'active';

    protected $fillable = [
        'user_id', 'provider', 'provider_subscription_id', 'provider_plan_id', 'status',
        'amount', 'currency', 'current_period_start', 'current_period_end', 'cancelled_at',
        'last_synced_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantsProAccess(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->current_period_end?->isFuture() === true;
    }
}
