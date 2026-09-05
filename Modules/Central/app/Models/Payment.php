<?php

namespace Modules\Central\Models;

use App\Enums\NameOfRoles;
use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

// use Modules\Central\Database\Factories\PaymentFactory;

class Payment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['subscription_id', 'amount', 'currency', 'payment_method', 'status', 'paid_at', 'gateway', 'transaction_id', 'metadata', 'checkout_session_id'];

    protected  $guarded = ['reference'];

    protected $casts = [
        'status' => PaymentStatus::class,
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];


    /**
     * Get the subscription that owns the payment.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user that owns the payment.
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $payment->reference = (string)Str::ulid();
        });
    }


    public function scopeOwnerPaymnets(Builder $query, User $user): Builder
    {
        if ($user->hasRole(NameOfRoles::SuperAdmin->value)) {
            return $query;
        }
        return $query->whereHas('subscription.price.plan', function ($q) use ($user) {
            return $q->where('owner_id', $user->id);
        });
    }



    // protected static function newFactory(): PaymentFactory
    // {
    //     // return PaymentFactory::new();
    // }
}
