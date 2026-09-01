<?php

namespace Modules\Central\Services\Payments;

use Illuminate\Support\Facades\Log;
use Modules\Central\Models\Payment;
use RuntimeException;
use Stripe\StripeClient;

class StripePaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(
            config('services.stripe.secret')
        );
    }

    /**
     * Create Stripe Checkout Session.
     *
     * The Checkout Session ID is stored in the payment because
     * the PaymentIntent/transaction_id may not exist before payment.
     */
    public function createCheckoutSession(Payment $payment): array
    {
        $payment->loadMissing('subscription.price.plan');

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',

            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($payment->currency),

                        'product_data' => [
                            'name' => $payment
                                ->subscription
                                ->price
                                ->plan
                                ->name,
                        ],

                        'unit_amount' => (int) round(
                            $payment->amount * 100
                        ),
                    ],

                    'quantity' => 1,
                ],
            ],

            'metadata' => [
                'payment_id' => (string) $payment->id,
                'payment_reference' => $payment->reference,
                'subscription_id' => (string) $payment->subscription_id,
            ],

            'payment_intent_data' => [
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'payment_reference' => $payment->reference,
                    'subscription_id' => (string) $payment->subscription_id,
                ],
            ],

            'success_url' => config('app.frontend_url')
                . '/payments/success?session_id={CHECKOUT_SESSION_ID}',

            'cancel_url' => config('app.frontend_url')
                . '/payments/cancel',
        ]);

        // حفظ Stripe Checkout Session ID
        $payment->update([
            'checkout_session_id' => $session->id,
        ]);

        Log::info('✅ Stripe Checkout Session created', [
            'payment_id' => $payment->id,
            'checkout_session_id' => $session->id,
        ]);

        return [
            'url' => $session->url,
            'session_id' => $session->id,
        ];
    }

    /**
     * Expire a Stripe Checkout Session.
     *
     * Used when the customer changes the subscription price
     * before completing the payment.
     */
    public function expireCheckoutSession(Payment $payment): void
    {
        if (!$payment->checkout_session_id) {
            throw new RuntimeException(
                'Stripe Checkout Session ID not found.'
            );
        }

        try {
            /*
             * Retrieve the current Checkout Session from Stripe.
             */
            $session = $this->stripe->checkout->sessions->retrieve(
                $payment->checkout_session_id
            );

            /*
             * Already expired.
             */
            if ($session->status === 'expired') {
                Log::info('Stripe Checkout Session already expired.', [
                    'payment_id' => $payment->id,
                    'checkout_session_id' => $session->id,
                ]);

                return;
            }

            /*
             * A completed session cannot be expired.
             *
             * This normally means the payment has already been completed,
             * so we must not silently mark it as canceled.
             */
            if ($session->status === 'complete') {
                throw new RuntimeException(
                    'Cannot expire a completed Stripe Checkout Session.'
                );
            }

            /*
             * Expire the Checkout Session.
             */
            $expiredSession = $this->stripe
                ->checkout
                ->sessions
                ->expire($payment->checkout_session_id);

            Log::info('Stripe Checkout Session expired successfully.', [
                'payment_id' => $payment->id,
                'checkout_session_id' => $expiredSession->id,
                'status' => $expiredSession->status,
            ]);
        } catch (\Exception $e) {

            Log::error(
                'Could not expire Stripe Checkout Session.',
                [
                    'payment_id' => $payment->id,
                    'checkout_session_id' =>
                    $payment->checkout_session_id,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Refund a successful Stripe payment.
     *
     * Used after the payment has already succeeded.
     */
    public function refundPayment(Payment $payment): array
    {
        if (!$payment->transaction_id) {
            throw new RuntimeException(
                'Stripe transaction ID not found.'
            );
        }

        $refund = $this->stripe->refunds->create([
            'payment_intent' => $payment->transaction_id,
        ]);

        return $refund->toArray();
    }

    /**
     * Cancel Stripe PaymentIntent.
     *
     * This is NOT used for an unpaid Checkout Session.
     *
     * Use expireCheckoutSession() when the customer has not
     * completed the Checkout Session yet.
     */
    public function cancelPayment(Payment $payment): array
    {
        if (!$payment->transaction_id) {
            throw new RuntimeException(
                'Stripe transaction ID not found.'
            );
        }

        /*
         * Retrieve PaymentIntent from Stripe.
         */
        $paymentIntent = $this->stripe
            ->paymentIntents
            ->retrieve(
                $payment->transaction_id
            );

        /*
         * Successful PaymentIntent cannot be canceled.
         * It must be refunded instead.
         */
        if ($paymentIntent->status === 'succeeded') {
            throw new RuntimeException(
                'Cannot cancel a payment that has already succeeded. ' .
                    'Use refundPayment() instead.'
            );
        }

        /*
         * Already canceled.
         */
        if ($paymentIntent->status === 'canceled') {
            return [
                'status' => 'canceled',
                'message' => 'PaymentIntent already canceled.',
                'payment_intent_id' => $paymentIntent->id,
            ];
        }

        /*
         * Cancel PaymentIntent.
         */
        $canceledIntent = $this->stripe
            ->paymentIntents
            ->cancel(
                $payment->transaction_id
            );

        /*
         * PaymentIntent ID normally does not change,
         * but keep the database synchronized just in case.
         */
        if ($canceledIntent->id !== $payment->transaction_id) {
            $payment->update([
                'transaction_id' => $canceledIntent->id,
            ]);
        }

        return [
            'status' => $canceledIntent->status,
            'payment_intent_id' => $canceledIntent->id,
            'cancelled_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get PaymentIntent status from Stripe.
     */
    public function getPaymentStatus(Payment $payment): ?string
    {
        if (!$payment->transaction_id) {
            return null;
        }

        try {
            $paymentIntent = $this->stripe
                ->paymentIntents
                ->retrieve(
                    $payment->transaction_id
                );

            return $paymentIntent->status;
        } catch (\Exception $e) {

            Log::warning(
                'Could not retrieve Stripe PaymentIntent status.',
                [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'error' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    /**
     * Check whether a Stripe PaymentIntent can be canceled.
     */
    public function canCancelPayment(Payment $payment): bool
    {
        if (!$payment->transaction_id) {
            return false;
        }

        try {
            $paymentIntent = $this->stripe
                ->paymentIntents
                ->retrieve(
                    $payment->transaction_id
                );

            $cancelableStatuses = [
                'requires_payment_method',
                'requires_confirmation',
                'requires_action',
                'processing',
            ];

            return in_array(
                $paymentIntent->status,
                $cancelableStatuses,
                true
            );
        } catch (\Exception $e) {

            Log::warning(
                'Could not check whether Stripe PaymentIntent can be canceled.',
                [
                    'payment_id' => $payment->id,
                    'transaction_id' => $payment->transaction_id,
                    'error' => $e->getMessage(),
                ]
            );
            return false;
        }
    }
}
