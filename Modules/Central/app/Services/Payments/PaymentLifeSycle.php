<?php

namespace Modules\Central\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionActivated;
use Illuminate\Support\Facades\DB;
use Modules\Central\Models\Payment;
use Modules\Central\Services\SubscriptionLifecycleService;
use Modules\Central\Services\Subscriptions\SubscriptionNotificationService;

use RuntimeException;

class PaymentLifeSycle
{
    /**
     *
     */
    public function __construct(
        public SubscriptionLifecycleService $subscriptionLifeService,
        public StripePaymentService $stripePaymentService,
        public SubscriptionNotificationService $subscriptionNotification,
        public PaymentNotification $paymentNotificaition,

    ) {}

    /**
     * Pay payment
     */
    public function pay(Payment $payment): Payment
    {
        $payment = DB::transaction(function () use ($payment) {

            $payment->loadMissing('subscription.price');

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new RuntimeException(
                    'Only pending payments can be paid.'
                );
            }

            if (!$payment->subscription) {
                throw new RuntimeException(
                    'Payment subscription not found.'
                );
            }

            if (!$payment->subscription->price) {
                throw new RuntimeException(
                    'Payment subscription price not found.'
                );
            }

            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ]);

            $data = $this->subscriptionLifeService
                ->prepareSubscriptionStatus(
                    [
                        'status' => SubscriptionStatus::ACTIVE,
                    ],
                    $payment->subscription->price,
                    $payment->subscription
                );

            $payment->subscription->update($data);

            return $payment
                ->fresh()
                ->load('subscription.price');
        });

        $subscription = $payment->subscription;

        DB::afterCommit(function () use ($subscription) {

            SubscriptionActivated::dispatch($subscription);
        });

        if ($subscription->previous_subscription_id !== null) {
            $this->subscriptionNotification->renewedNotification(
                $subscription->id
            );
        } else {
            $this->paymentNotificaition->successPaid(
                $subscription->id
            );
        }

        return $payment;
    }

    /**
     * Cancel payment
     */
    public function cancel(Payment $payment): Payment
    {
        $payment = DB::transaction(function () use ($payment) {

            $payment->loadMissing('subscription.price');

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new RuntimeException(
                    'Only pending payments can be canceled.'
                );
            }

            if (!$payment->subscription) {
                throw new RuntimeException(
                    'Payment subscription not found.'
                );
            }

            if (!$payment->subscription->price) {
                throw new RuntimeException(
                    'Payment subscription price not found.'
                );
            }

            $payment->update([
                'status' => PaymentStatus::CANCELED,
            ]);

            $data = $this->subscriptionLifeService
                ->prepareSubscriptionStatus(
                    [
                        'status' => SubscriptionStatus::CANCELED,
                    ],
                    $payment->subscription->price,
                    $payment->subscription
                );

            $payment->subscription->update($data);

            return $payment
                ->fresh()
                ->load('subscription.price');
        });

        DB::afterCommit(function () use ($payment) {
            $this->paymentNotificaition->cancelPayment(
                $payment->subscription->id
            );
        });

        return $payment;
    }

    /**
     * Retry payment
     */
    public function retry(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {

            $payment->loadMissing('subscription.price');

            if ($payment->status !== PaymentStatus::FAILED) {
                throw new RuntimeException(
                    'Only failed payments can be retried.'
                );
            }

            if (!$payment->subscription) {
                throw new RuntimeException(
                    'Payment subscription not found.'
                );
            }

            if (
                $payment->subscription->status !==
                SubscriptionStatus::PENDING
            ) {
                throw new RuntimeException(
                    'Only pending subscriptions can retry payment.'
                );
            }

            $payment->update([
                'status' => PaymentStatus::PENDING,
                'paid_at' => null,
                'transaction_id' => null,
            ]);

            $checkoutUrl = $this->stripePaymentService
                ->createCheckoutSession($payment);
            $checkoutUrl = $checkoutUrl['url'];


            $this->paymentNotificaition->retryNotification(
                $payment->subscription->id,
                $checkoutUrl
            );

            $payment->setAttribute(
                'checkout_url',
                $checkoutUrl
            );

            return $payment->load('subscription.price');
        });
    }

    /**
     * Refund payment
     */
    public function refund(Payment $payment): Payment
    {
        $payment->loadMissing('subscription.price');
        if ($payment->status !== PaymentStatus::PAID) {
            throw new RuntimeException(
                'Only paid payments can be refunded.'
            );
        }
        if (!$payment->subscription) {
            throw new RuntimeException(
                'Payment subscription not found.'
            );
        }
        if (!$payment->subscription->price) {
            throw new RuntimeException(
                'Payment subscription price not found.'
            );
        }
        $stripeRefund = $this->stripePaymentService
            ->refundPayment($payment);

        if (($stripeRefund['status'] ?? null) !== 'succeeded') {
            throw new RuntimeException(
                'Stripe refund was not successful.'
            );
        }
        $payment = DB::transaction(function () use (
            $payment,
            $stripeRefund
        ) {
            $payment->update([
                'status' => PaymentStatus::REFUNDED,
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'stripe_refund_id' =>
                        $stripeRefund['id'] ?? null,

                        'stripe_refund_status' =>
                        $stripeRefund['status'] ?? null,
                    ]
                ),
            ]);
            $data = $this->subscriptionLifeService
                ->prepareSubscriptionStatus(
                    [
                        'status' => SubscriptionStatus::CANCELED,
                    ],
                    $payment->subscription->price,
                    $payment->subscription
                );

            $payment->subscription->update($data);

            return $payment
                ->fresh()
                ->load('subscription.price');
        });

        DB::afterCommit(function () use ($payment) {

            $this->paymentNotificaition->refundPayment(
                $payment->subscription->id
            );
        });

        return $payment;
    }

    /**
     * Fail payment
     */
    public function fail(
        Payment $payment,
        ?string $reason = null
    ): Payment {
        $payment = DB::transaction(function () use ($payment, $reason) {

            $payment->loadMissing('subscription.price');

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new RuntimeException(
                    'Only pending payments can fail.'
                );
            }

            $payment->update([
                'status' => PaymentStatus::FAILED,
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'failure_reason' => $reason,
                    ]
                ),
            ]);

            return $payment
                ->fresh()
                ->load('subscription.price');
        });

        $this->paymentNotificaition->failedPaid(
            $payment->subscription->id,
            $reason
        );

        return $payment;
    }
}
