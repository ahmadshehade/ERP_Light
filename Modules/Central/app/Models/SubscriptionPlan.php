<?php

namespace Modules\Central\Models;

use App\Enums\NameOfRoles;
use App\Enums\PermissionManagementPermissions;
use App\Models\User;
use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;
use Modules\Central\Models\SubscriptionPrice;

// use Modules\Central\Database\Factories\SubscriptionPlanFactory;

class SubscriptionPlan extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;
    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'name' => 'json',
        'description' => 'json',
        "is_active" => "boolean",
    ];


    public $translatable = [
        'name',
        'description',
    ];

    /**
     * Scope a query to only include active users.
     * @return Builder
     * @throws \Exception
     */
    public function scopeActive(Builder $query, User $user): Builder
    {
        if (

            ($user->hasRole(NameOfRoles::SuperAdmin->value)
            )
        ) {
            return $query;
        }
        return $query->where('is_active', true);
    }



    /**
     * Create a new factory instance for the model.
     */
    protected static  function newFactory()
    {
        return SubscriptionPlanFactory::new();
    }

    /**
     * Get all of the subscriptionPrices for the SubscriptionPlan
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptionPrices(): HasMany
    {
        return $this->hasMany(SubscriptionPrice::class, 'plan_id');
    }
}
