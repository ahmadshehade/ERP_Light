<?php

namespace Modules\Central\Services;

use App\Enums\PriceInterval;
use App\Enums\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Central\Models\Company;
use Modules\Central\Models\Subscription;
use Modules\Central\Models\SubscriptionPrice;
use RuntimeException;

class SubscriptionLifecycleService
{
    /**
     * Prepare subscription data
     */
    public function prepareSubscriptionData(array $data): array
    {
        if (isset($data['company_id'])) {
            $data['company_id'] = Company::query()
                ->userCompanies(Auth::user())
                ->where('is_active', true)
                ->findOrFail($data['company_id'])
                ->id;
        }

        if (isset($data['price_id'])) {
            $data['price_id'] = SubscriptionPrice::query()
                ->where('is_active', true)
                ->findOrFail($data['price_id'])
                ->id;
        }

        return $data;
    }

    /**
     * Calculate end date
     */
    public function calculateEndDate(
        Carbon $startDate,
        SubscriptionPrice $subscriptionPrice
    ): Carbon {
        return match ($subscriptionPrice->interval) {
            PriceInterval::DAY   => $startDate->copy()->addDay(),
            PriceInterval::WEEK  => $startDate->copy()->addWeek(),
            PriceInterval::MONTH => $startDate->copy()->addMonth(),
            PriceInterval::YEAR  => $startDate->copy()->addYear(),
        };
    }

    /**
     * Validate and prepare subscription status
     */
    public function prepareSubscriptionStatus(
        array $data,
        SubscriptionPrice $subscriptionPrice,
        Subscription $subscription
    ): array {
        /*
         * Because Subscription model casts status to SubscriptionStatus,
         * $subscription->status will normally be an Enum instance.
         */
        $oldStatus = $subscription->status;

        /*
         * The incoming status may be:
         * - SubscriptionStatus enum
         * - string value
         * - not provided
         */
        $newStatus = $data['status'] ?? $oldStatus;

        if (!$newStatus instanceof SubscriptionStatus) {
            $newStatus = SubscriptionStatus::from($newStatus);
        }

        /*
         * Pending cannot return from Active
         */
        if (
            $oldStatus === SubscriptionStatus::ACTIVE &&
            $newStatus === SubscriptionStatus::PENDING
        ) {
            throw new RuntimeException(
                'Cannot return active subscription to pending.'
            );
        }

        /*
         * Cannot reactivate canceled subscription
         */
        if (
            $oldStatus === SubscriptionStatus::CANCELED &&
            $newStatus === SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Cannot activate canceled subscription.'
            );
        }

        /*
         * Trial expired subscription cannot be activated
         */
        if (
            $oldStatus === SubscriptionStatus::TRAIL_EXPIRED &&
            $newStatus === SubscriptionStatus::ACTIVE
        ) {
            throw new RuntimeException(
                'Trial expired subscription cannot be activated. Create a new subscription.'
            );
        }

        /*
         * Cancel
         */
        if ($newStatus === SubscriptionStatus::CANCELED) {
            if (
                in_array(
                    $oldStatus,
                    [
                        SubscriptionStatus::ACTIVE,
                        SubscriptionStatus::PENDING,
                    ],
                    true
                )
            ) {
                $data['canceled_at'] = now();
            }

            $data['status'] = SubscriptionStatus::CANCELED;

            return $data;
        }

        /*
         * Activate
         */
        if ($newStatus === SubscriptionStatus::ACTIVE) {
            if ($oldStatus !== SubscriptionStatus::PENDING) {
                throw new RuntimeException(
                    'Only pending subscriptions can be activated.'
                );
            }

            $data['status'] = SubscriptionStatus::ACTIVE;
            $data['start_date'] = now();

            $data['end_date'] = $this->calculateEndDate(
                $data['start_date'],
                $subscriptionPrice
            );

            $data['trial_end_date'] = null;
        }

        /*
         * Keep status as Enum.
         * Laravel will convert it to its backing value because
         * the model has an Enum cast.
         */
        if (!isset($data['status'])) {
            $data['status'] = $newStatus;
        }

        return $data;
    }

    /**
     * Prepare trial data
     */
    public function prepareTrialData(
        array $data,
        SubscriptionPrice $price
    ): array {
        $status = $data['status']
            ?? SubscriptionStatus::PENDING;

        if (!$status instanceof SubscriptionStatus) {
            $status = SubscriptionStatus::from($status);
        }

        if (
            $status === SubscriptionStatus::PENDING &&
            $price->trial_days > 0
        ) {
            $data['trial_end_date'] = now()->addDays(
                $price->trial_days
            );
        }

        return $data;
    }

    /**
     * Refresh subscription status
     */
    public function refreshStatus(Subscription $subscription): void
    {
        /*
         * Because of the cast, status is an Enum.
         */
        if (
            $subscription->trial_end_date &&
            $subscription->trial_end_date->isPast() &&
            $subscription->status === SubscriptionStatus::PENDING
        ) {
            $subscription->update([
                'status' => SubscriptionStatus::TRAIL_EXPIRED,
            ]);
        } elseif (
            $subscription->end_date &&
            $subscription->end_date->isPast()
        ) {
            $subscription->update([
                'status' => SubscriptionStatus::EXPIRED,
            ]);
        }
    }

    /**
     * Renew subscription
     */

    public function renew(
        Subscription $subscription,
        array $data
    ): Subscription {
        return DB::transaction(function () use ($subscription, $data) {

            if (
                !in_array(
                    $subscription->status,
                    [
                        SubscriptionStatus::ACTIVE,
                        SubscriptionStatus::EXPIRED,
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'Only active or expired subscriptions can be renewed.'
                );
            }
            $exists = Subscription::query()
                ->where('company_id', $subscription->company_id)
                ->where(
                    'status',
                    SubscriptionStatus::PENDING->value
                )
                ->exists();

            if ($exists) {
                throw new RuntimeException(
                    'Company already has a pending subscription.'
                );
            }

            $price = SubscriptionPrice::query()
                ->where('is_active', true)
                ->findOrFail($data['price_id']);

            $newSubscription = new Subscription();

            $newSubscription->company_id =
                $subscription->company_id;

            $newSubscription->price_id =
                $price->id;

            $newSubscription->status =
                SubscriptionStatus::PENDING;

            $newSubscription->previous_subscription_id =
                $subscription->id;

            $newSubscription->trial_end_date = null;

            $newSubscription->save();


            return $newSubscription->load([
                'company.owner',
                'price',
            ]);
        });
    }
}
