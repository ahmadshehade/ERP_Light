<?php

namespace Modules\Central\Services\Subscriptions;

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Central\Notifications\Api\V1\Subscriptions\SubscriptionCreatedNotification;
use Illuminate\Support\Facades\Log;
use Modules\Central\Models\Subscription;
use Modules\Central\Notifications\Api\V1\Subscriptions\RenewSubscriptionNotification;
use Modules\Central\Notifications\Api\V1\Subscriptions\SubscriptionRenewedNotification;
use Modules\Central\Notifications\Api\V1\Subscriptions\UpdateSubscriptionNotification;
use RuntimeException;

class SubscriptionNotificationService
{
    public function SubscriptionCreateNotification(
        int $subscriptionId,
        string $checkoutUrl
    ): void {

        Log::info('📧 Sending notification with checkout URL', [
            'subscription_id' => $subscriptionId,
            'checkout_url' => $checkoutUrl,
        ]);

        $subscription = \Modules\Central\Models\Subscription::with([
            'company.owner',
        ])->findOrFail($subscriptionId);

        $owner = $subscription->company->owner;
        $admins = User::role(NameOfRoles::SuperAdmin->value)->get();
        $recipients = $admins->push($owner)->unique('id');

        Notification::send(
            $recipients,
            new SubscriptionCreatedNotification(
                subscriptionId: $subscriptionId,
                checkoutUrl: $checkoutUrl,
            )
        );
    }

    public function ChangedNotification(
        int $subscriptionId,
        string $checkoutUrl,
        array $changes = [],
        ?string $oldPlanName = null,
        ?string $newPlanName = null,
        ?float $oldPrice = null,
        ?float $newPrice = null,
    ): void {
        $subscription = Subscription::with([
            'company.owner',
        ])->findOrFail($subscriptionId);

        $users = collect();

        if ($subscription->company?->owner) {
            $users->push($subscription->company->owner);
        }

        $users = $users
            ->unique('id')
            ->values();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send(
            $users,
            new UpdateSubscriptionNotification(
                subscriptionId: $subscriptionId,
                checkoutUrl: $checkoutUrl,
                changes: $changes,
                oldPlanName: $oldPlanName,
                newPlanName: $newPlanName,
                oldPrice: $oldPrice,
                newPrice: $newPrice,
            )
        );
    }


    /**
     * Send renew notification
     * @param Subscription $subscription
     * @param string $checkoutUrl
     */
    public function renewNotification(
        Subscription $subscription,
        string $checkoutUrl,
    ): void {
        $subscription->loadMissing([
            'company.owner',
            'price.plan',
        ]);

        $company = $subscription->company;
        $price = $subscription->price;
        $plan = $price?->plan;

        if (!$company || !$price || !$plan) {
            Log::warning(
                ' Cannot send renewal notification: incomplete subscription data',
                [
                    'subscription_id' => $subscription->id,
                ]
            );
            throw new RuntimeException(
                ' Cannot send renewal notification: incomplete subscription data'
            );
        }

        $companyName = $company->getTranslation(
            'name',
            'en'
        );

        $planName = $plan->getTranslation(
            'name',
            'en'
        );

        $priceValue = (float) $price->price;

        $interval = $price->interval?->value ?? 'N/A';

        $admins = User::role(
            NameOfRoles::SuperAdmin->value
        )->get();

        $owner = $company->owner;

        $recipients = $admins;

        if ($owner) {
            $recipients->push($owner);
        }

        $recipients = $recipients
            ->filter()
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            Log::warning(
                ' No recipients found for renewal notification',
                [
                    'subscription_id' => $subscription->id,
                ]
            );

            return;
        }

        Notification::send(
            $recipients,
            new RenewSubscriptionNotification(
                subscriptionId: $subscription->id,
                companyName: $companyName,
                planName: $planName,
                price: $priceValue,
                interval: $interval,
                checkoutUrl: $checkoutUrl,
            )
        );
    }

    public function renewedNotification(
        int $subscriptionId
    ): void {
        $subscription = Subscription::with([
            'company.owner',
            'price.plan',
        ])->findOrFail($subscriptionId);

        $owner = $subscription->company?->owner;

        $admins = User::role(
            NameOfRoles::SuperAdmin->value
        )->get();

        $recipients = $admins;

        if ($owner) {
            $recipients->push($owner);
        }

        $recipients = $recipients
            ->filter()
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $companyName = $subscription->company
            ->getTranslation('name', 'en');

        $planName = $subscription->price->plan
            ->getTranslation('name', 'en');

        $price = (float) $subscription->price->price;

        $interval = $subscription->price->interval->value;

        Notification::send(
            $recipients,
            new SubscriptionRenewedNotification(
                subscriptionId: $subscription->id,
                companyName: $companyName,
                planName: $planName,
                priceValue: $price,
                interval: $interval,
            )
        );
    }
}
