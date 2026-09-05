<?php

namespace Modules\Central\Models;

use App\Enums\NameOfRoles;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Override;
use Spatie\Activitylog\Traits\LogsActivity;

// use Modules\Central\Database\Factories\SubscriptionFactory;

class Subscription extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['price_id', 'company_id', 'status', 'start_date', 'end_date', 'trial_end_date', 'canceled_at'];

    protected $guarded = ['previous_subscription_id'];

    /**
     * Get the company that owns the subscription.
     */
    protected $casts = [
        'status' => SubscriptionStatus::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'trial_end_date' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    /**
     * Get the company that owns the subscription.
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the price that owns the subscription.
     * @return BelongsTo
     */
    public function price(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPrice::class, 'price_id');
    }

    /**
     * Scope a query to only include user subscriptions.
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public  function scopeUserSubscriptions(Builder $query, User $user): Builder
    {
        if ($user->hasRole(NameOfRoles::SuperAdmin)) {
            return $query;
        }
        if ($user->hasRole(NameOfRoles::Owner)) {
            return $query->whereHas('company', function ($q) use ($user) {
                return $q->where('owner_id', $user->id);
            });
        }
        return $query->whereRaw("1=0");
    }

    /**
     * Get the payment that owns the subscription.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    /**
     * Get the options for generating the activity log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }







    // protected static function newFactory(): SubscriptionFactory
    // {
    //     // return SubscriptionFactory::new();
    // }
}
