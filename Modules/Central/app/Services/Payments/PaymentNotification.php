<?php

namespace Modules\Central\Services\Payments;

use Illuminate\Support\Facades\Notification;
use Modules\Central\Models\Subscription;
use Modules\Central\Notifications\Api\V1\CancelPaymentNotification;
use Modules\Central\Notifications\Api\V1\FailedPaymentNotification;
use Modules\Central\Notifications\Api\V1\RefundPaymentNotification;
use Modules\Central\Notifications\Api\V1\RetryPaymentNotification;
use Modules\Central\Notifications\Api\V1\SuccessPaymentNotification;

class PaymentNotification
{

    /**
     *
     */
    public function successPaid(int $subscriptionId)
    {
        $subscription = Subscription::with([
            'company.owner',
            'price.plan',
        ])->findOrFail($subscriptionId);

        $owner = $subscription->company?->owner;


        $companyName = $subscription->company
            ->getTranslation('name', 'en');

        $planName = $subscription->price->plan
            ->getTranslation('name', 'en');

        $price = (float) $subscription->price->price;

        $interval = $subscription->price->interval->value;

        Notification::send(
            $owner,
            new SuccessPaymentNotification(
                subscriptionId: $subscription->id,
                companyName: $companyName,
                planName: $planName,
                priceValue: $price,
                interval: $interval,
            )
        );
    }
    /**
     *
     */
    public function failedPaid(int $subscriptionId, ?string $reason = null)
    {
        $subscription = Subscription::with([
            'company.owner',
            'price.plan',
        ])->findOrFail($subscriptionId);

        $owner = $subscription->company?->owner;

        $companyName = $subscription->company
            ->getTranslation('name', 'en');

        $planName = $subscription->price->plan
            ->getTranslation('name', 'en');

        $price = (float) $subscription->price->price;

        $interval = $subscription->price->interval->value;

        Notification::send(
            $owner,
            new FailedPaymentNotification(
                subscriptionId: $subscription->id,
                companyName: $companyName,
                planName: $planName,
                price: $price,
                interval: $interval,
                reason: $reason,
            )
        );
    }


    /**
     *
     */
    public function retryNotification(
        int $subscriptionId,
        string $checkoutUrl
    ): void {
        $subscription = Subscription::with([
            'company.owner',
            'price.plan',
        ])->findOrFail($subscriptionId);

        $owner = $subscription->company?->owner;

        if (!$owner) {
            return;
        }

        $companyName = $subscription->company
            ->getTranslation('name', 'en');

        $planName = $subscription->price->plan
            ->getTranslation('name', 'en');

        Notification::send(
            $owner,
            new RetryPaymentNotification(
                subscriptionId: $subscription->id,
                companyName: $companyName,
                planName: $planName,
                checkoutUrl: $checkoutUrl,
            )
        );
    }

    /**
     *
     */
    public function cancelPayment(int $subscriptionId): void
    {
        $subscription = Subscription::with([
            'company.owner',
            'price.plan',
        ])->findOrFail($subscriptionId);

        $owner = $subscription->company?->owner;

        if (!$owner) {
            return;
        }

        $companyName = $subscription->company
            ->getTranslation('name', 'en');

        $planName = $subscription->price->plan
            ->getTranslation('name', 'en');

        Notification::send(
            $owner,
            new CancelPaymentNotification(
                subscriptionId: $subscription->id,
                companyName: $companyName,
                planName: $planName,
                price: (float) $subscription->price->price,
                interval: $subscription->price->interval->value
            )
        );
    }


    /**
     *
     */
    public function refundPayment(int $subscriptionId): void
    {
        $subscription = Subscription::with([
            'company.owner',
            'price.plan',
        ])->findOrFail($subscriptionId);

        $owner = $subscription->company?->owner;

        if (!$owner) {
            return;
        }

        $companyName = $subscription->company
            ->getTranslation('name', 'en');

        $planName = $subscription->price->plan
            ->getTranslation('name', 'en');

        $price = (float) $subscription->price->price;

        $interval = $subscription->price->interval->value;

        Notification::send(
            $owner,
            new RefundPaymentNotification(
                subscriptionId: $subscription->id,
                companyName: $companyName,
                planName: $planName,
                price: $price,
                interval: $interval,
            )
        );
    }
}
