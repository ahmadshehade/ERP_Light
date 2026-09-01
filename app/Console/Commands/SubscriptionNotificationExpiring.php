<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Modules\Central\Models\Subscription;
use Modules\Central\Notifications\Api\V1\Subscriptions\NotifyExpiringSubscription;

class SubscriptionNotificationExpiring extends Command
{
    protected $signature = 'subscription:notification-expiring';

    protected $description = 'Send notification for expiring subscriptions';

    public function handle()
    {
        $subscriptions = Subscription::with([
            'company.owner',
            'price.plan',
        ])
            ->where('status', SubscriptionStatus::ACTIVE)
            ->whereBetween('end_date', [
                now(),
                now()->addDay(),
            ])
            ->get();

        foreach ($subscriptions as $subscription) {

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
                new NotifyExpiringSubscription(
                    subscriptionId: $subscription->id,
                    companyName: $companyName,
                    planName: $planName,
                    end_date: $subscription->end_date,
                )
            );
        }

        $this->info(
            "Expiring subscriptions notifications sent: {$subscriptions->count()}"
        );

        return self::SUCCESS;
    }
}
