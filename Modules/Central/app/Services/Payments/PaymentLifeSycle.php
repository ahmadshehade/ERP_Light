<?php

namespace Modules\Central\Services\Payments;

use App\Enums\NameOfRoles;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionActivated;
use App\Exceptions\BusinessRuleException;
use Illuminate\Support\Facades\DB;
use Modules\Central\Models\Payment;
use Modules\Central\Services\SubscriptionLifecycleService;
use Modules\Central\Services\Subscriptions\SubscriptionNotificationService;

class PaymentLifeSycle
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        public SubscriptionLifecycleService $subscriptionLifeService,
        public StripePaymentService $stripePaymentService,
        public SubscriptionNotificationService $subscriptionNotification,
        public PaymentNotification $paymentNotificaition,
    ) {}

    /**
     * Pay payment.
     */
    public function pay(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {

            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            /*
             * Webhooks may be delivered more than once.
             *
             * If this payment was already paid, consider the
             * repeated webhook successfully handled.
             */
            if ($payment->status === PaymentStatus::PAID) {
                return $payment->fresh([
                    'subscription.price',
                ]);
            }

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new BusinessRuleException(
                    'Only pending payments can be paid.',
                    409
                );
            }

            $payment->loadMissing('subscription.price');

            if (!$payment->subscription) {
                throw new BusinessRuleException(
                    'Payment subscription not found.',
                    404
                );
            }

            if (!$payment->subscription->price) {
                throw new BusinessRuleException(
                    'Payment subscription price not found.',
                    404
                );
            }

            $subscription = $payment->subscription;

            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => now(),
            ]);

            $data = $this->subscriptionLifeService
                ->prepareSubscriptionStatus(
                    [
                        'status' => SubscriptionStatus::ACTIVE,
                    ],
                    $subscription->price,
                    $subscription
                );

            $subscription->update($data);

            $subscription->company->owner->assignRole(
                NameOfRoles::Owner->value
            );

            DB::afterCommit(function () use ($subscription) {

                SubscriptionActivated::dispatch($subscription);

                if ($subscription->previous_subscription_id !== null) {

                    $this->subscriptionNotification
                        ->renewedNotification($subscription->id);
                } else {

                    $this->paymentNotificaition
                        ->successPaid($subscription->id);
                }
            });

            return $payment->fresh([
                'subscription.price',
            ]);
        }, 5);
    }

    /**
     * Cancel payment.
     */
    public function cancel(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {

            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $payment->loadMissing('subscription.price');

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new BusinessRuleException(
                    'Only pending payments can be canceled.',
                    409
                );
            }

            if (!$payment->subscription) {
                throw new BusinessRuleException(
                    'Payment subscription not found.',
                    404
                );
            }

            if (!$payment->subscription->price) {
                throw new BusinessRuleException(
                    'Payment subscription price not found.',
                    404
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

            $payment = $payment
                ->fresh()
                ->load('subscription.price');

            $subscriptionId = $payment->subscription->id;

            DB::afterCommit(function () use ($subscriptionId) {

                $this->paymentNotificaition
                    ->cancelPayment($subscriptionId);
            });

            return $payment;
        }, 5);
    }

    /**
     * Retry payment.
     */
    public function retry(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {

            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $payment->loadMissing('subscription.price');

            if ($payment->status !== PaymentStatus::FAILED) {
                throw new BusinessRuleException(
                    'Only failed payments can be retried.',
                    409
                );
            }

            if (!$payment->subscription) {
                throw new BusinessRuleException(
                    'Payment subscription not found.',
                    404
                );
            }

            if ($payment->subscription->status !== SubscriptionStatus::PENDING) {
                throw new BusinessRuleException(
                    'Only pending subscriptions can retry payment.',
                    409
                );
            }

            /*
             * The payment row is locked until the transaction
             * finishes, so another retry request cannot change
             * the same payment concurrently.
             */
            $payment->update([
                'status' => PaymentStatus::PENDING,
                'paid_at' => null,
                'transaction_id' => null,
            ]);

            /*
             * Keep Stripe operation inside the same transaction
             * so another retry cannot start for the same payment.
             */
            $checkout = $this->stripePaymentService
                ->createCheckoutSession($payment);

            $checkoutUrl = $checkout['url'];

            $payment->setAttribute(
                'checkout_url',
                $checkoutUrl
            );

            $subscriptionId = $payment->subscription->id;

            DB::afterCommit(function () use (
                $subscriptionId,
                $checkoutUrl
            ) {

                $this->paymentNotificaition
                    ->retryNotification(
                        $subscriptionId,
                        $checkoutUrl
                    );
            });

            return $payment->load('subscription.price');
        }, 5);
    }

    /**
     * Refund payment.
     */
    public function refund(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {

            /*
             * IMPORTANT:
             * The lock must be inside the transaction.
             */
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            $payment->loadMissing('subscription.price');

            if ($payment->status !== PaymentStatus::PAID) {
                throw new BusinessRuleException(
                    'Only paid payments can be refunded.',
                    409
                );
            }

            if (!$payment->subscription) {
                throw new BusinessRuleException(
                    'Payment subscription not found.',
                    404
                );
            }

            if (!$payment->subscription->price) {
                throw new BusinessRuleException(
                    'Payment subscription price not found.',
                    404
                );
            }

            /*
             * Because the payment row is locked, another refund
             * request for the same payment must wait here.
             *
             * After the first request changes the status to
             * REFUNDED, the second request will fail the PAID check.
             */
            $stripeRefund = $this->stripePaymentService
                ->refundPayment($payment);

            if (($stripeRefund['status'] ?? null) !== 'succeeded') {
                throw new BusinessRuleException(
                    'Stripe refund was not successful.',
                    500
                );
            }

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

            $payment = $payment
                ->fresh()
                ->load('subscription.price');

            $subscriptionId = $payment->subscription->id;

            DB::afterCommit(function () use ($subscriptionId) {

                $this->paymentNotificaition
                    ->refundPayment($subscriptionId);
            });

            return $payment;
        }, 5);
    }

    /**
     * Fail payment.
     */
    public function fail(
        Payment $payment,
        ?string $reason = null
    ): Payment {

        return DB::transaction(function () use ($payment, $reason) {

            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            /*
             * Webhook can be delivered more than once.
             *
             * If it is already failed, treat the repeated
             * webhook as already processed.
             */
            if ($payment->status === PaymentStatus::FAILED) {
                return $payment->fresh([
                    'subscription.price',
                ]);
            }

            if ($payment->status !== PaymentStatus::PENDING) {
                throw new BusinessRuleException(
                    'Only pending payments can fail.',
                    409
                );
            }

            $payment->loadMissing('subscription.price');

            if (!$payment->subscription) {
                throw new BusinessRuleException(
                    'Payment subscription not found.',
                    404
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

            $payment = $payment
                ->fresh()
                ->load('subscription.price');

            $subscriptionId = $payment->subscription->id;

            DB::afterCommit(function () use (
                $subscriptionId,
                $reason
            ) {

                $this->paymentNotificaition
                    ->failedPaid(
                        $subscriptionId,
                        $reason
                    );
            });

            return $payment;
        }, 5);
    }
}
