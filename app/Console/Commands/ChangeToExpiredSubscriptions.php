<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Modules\Central\Models\Subscription;
use Modules\Central\Notifications\Api\V1\Subscriptions\SubscriptionExpiredNotification;
use Modules\Central\Notifications\Api\V1\Subscriptions\TrialExpiredNotification;

class ChangeToExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:change-to-expired';

    protected $description = 'Change expired subscriptions status and send notifications';

    public function handle(): int
    {
        /*
         * ============================================================
         * Active subscriptions expired
         * ============================================================
         */

        $subscriptions = Subscription::with([
            'company.owner',
            'price.plan',
        ])
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {

            $subscription->update([
                'status' => SubscriptionStatus::EXPIRED->value,
            ]);

            $owner = $subscription->company?->owner;

            if (!$owner) {
                continue;
            }

            $companyName = $subscription->company
                ->getTranslation('name', 'en');

            $planName = $subscription->price->plan
                ->getTranslation('name', 'en');

            Notification::send(
                $owner,
                new SubscriptionExpiredNotification(
                    subscriptionId: $subscription->id,
                    companyName: $companyName,
                    planName: $planName,
                )
            );
        }

        $expiredCount = $subscriptions->count();


        /*
 * ============================================================
 * Trial subscriptions expired without activation
 * ============================================================
 */

        $trialSubscriptions = Subscription::with([
            'company.owner',
            'price.plan',
        ])
            ->where('status', SubscriptionStatus::PENDING->value)
            ->whereNotNull('trial_end_date')
            ->where('trial_end_date', '<=', now())
            ->get();

        foreach ($trialSubscriptions as $subscription) {

            $subscription->update([
                'status' => SubscriptionStatus::TRAIL_EXPIRED->value,
            ]);

            $owner = $subscription->company?->owner;

            if (!$owner) {
                continue;
            }

            $companyName = $subscription->company
                ->getTranslation('name', 'en');

            $planName = $subscription->price->plan
                ->getTranslation('name', 'en');

            Notification::send(
                $owner,
                new TrialExpiredNotification(
                    subscriptionId: $subscription->id,
                    companyName: $companyName,
                    planName: $planName,
                    trialEndDate: $subscription->trial_end_date
                        ->format('Y-m-d H:i'),
                )
            );
        }

        $trialExpiredCount = $trialSubscriptions->count();


        /*
         * ============================================================
         * Result
         * ============================================================
         */

        $total = $expiredCount + $trialExpiredCount;

        $this->info(
            "{$expiredCount} active subscriptions expired."
        );

        $this->info(
            "{$trialExpiredCount} trial subscriptions expired."
        );

        $this->info(
            "{$total} subscriptions changed to expired."
        );

        return self::SUCCESS;
    }
}
