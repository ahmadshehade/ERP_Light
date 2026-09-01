<?php

namespace Modules\Central\Models;

use App\Enums\NameOfRoles;
use App\Enums\PermissionManagementPermissions;
use App\Enums\PriceInterval;
use App\Models\User;
use Database\Factories\SubscrtiptionPriceFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Central\Database\Factories\SubscriptionPriceFactory;

class SubscriptionPrice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ["plan_id", "price", "interval", "stripe_price_id", "is_active", "has_trial", "trial_days"];

    protected $table = "subscription_prices";


    /**
     * The attributes that should be cast.
     * @return array
     */
    protected function casts(): array
    {
        return [
            'interval' => PriceInterval::class,
            'is_active' => 'boolean',
            'price' => 'decimal:2'
        ];
    }



    /**
     * Get the plan that owns the subscription price.
     * @return BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }


    /**
     * Scope a query to only include active subscription prices.
     * @param User $user
     * @param Builder $query
     * @return Builder
     * @throws \Exception
     */
    public  function scopeActive(Builder $query, User $user): Builder
    {
        if ($user->hasRole(NameOfRoles::SuperAdmin->value)) {
            return $query;
        } elseif ($user->can(PermissionManagementPermissions::ViewAnySubscriptionPrices->value)) {
            return $query->where('is_active', true);
        }
        return $query->whereRaw('1 = 0');
    }

    /**
     * Create a new factory instance for the model.
     * @return SubscrtiptionPriceFactory $subscriptionPriceFactory
     *
     */
    protected static function newFactory(): SubscrtiptionPriceFactory
    {
        return SubscrtiptionPriceFactory::new();
    }

    /**
     * Get all of the subscriptions for the SubscriptionPrice
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'price_id');
    }
}
