<?php

namespace Modules\Central\Http\Controllers\Api\V1\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Central\Models\Payment;
use Modules\Central\Services\Payments\PaymentLifeSycle;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected PaymentLifeSycle $paymentLifeSycle
    ) {}

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (UnexpectedValueException $e) {
            logger()->error('Invalid Stripe webhook payload', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid payload',
            ], 400);
        } catch (SignatureVerificationException $e) {
            logger()->error('Invalid Stripe webhook signature', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 400);
        }

        logger()->info('Stripe webhook received', [
            'event_id' => $event->id,
            'event_type' => $event->type,
        ]);

        switch ($event->type) {

            /*
             * ============================================================
             * CHECKOUT SESSION COMPLETED
             * ============================================================
             */
            case 'checkout.session.completed':

                $session = $event->data->object;

                $paymentId = $session->metadata->payment_id ?? null;

                logger()->info('Stripe checkout completed', [
                    'session_id' => $session->id,
                    'payment_id' => $paymentId,
                    'payment_reference' =>
                    $session->metadata->payment_reference ?? null,
                    'subscription_id' =>
                    $session->metadata->subscription_id ?? null,
                    'payment_status' =>
                    $session->payment_status ?? null,
                ]);

                if (($session->payment_status ?? null) !== 'paid') {
                    logger()->warning(
                        'Stripe checkout completed but payment is not paid',
                        [
                            'session_id' => $session->id,
                            'payment_status' => $session->payment_status ?? null,
                        ]
                    );

                    break;
                }


                $payment = Payment::query()->find($paymentId);

                if (!$payment) {
                    logger()->warning(
                        'Payment not found for Stripe checkout',
                        [
                            'payment_id' => $paymentId,
                            'session_id' => $session->id,
                        ]
                    );

                    break;
                }

                /*
     * checkout.session.completed لا يقوم بالدفع.
     *
     * payment_intent.succeeded هو المسؤول عن:
     *
     * - دفع Payment
     * - تفعيل Subscription
     * - تحديد start_date
     * - تحديد end_date
     * - إرسال إشعارات الدفع
     *
     * هنا نقوم فقط بتخزين معلومات Stripe.
     */

                $payment->update([
                    'gateway' => 'stripe',
                    'transaction_id' =>
                    $session->payment_intent ?? $session->id,
                    'metadata' => array_merge(
                        $payment->metadata ?? [],
                        [
                            'stripe_session_id' => $session->id,
                            'stripe_payment_intent_id' =>
                            $session->payment_intent ?? null,
                        ]
                    ),
                ]);

                logger()->info(
                    'Stripe checkout information stored',
                    [
                        'payment_id' => $payment->id,
                        'payment_status' => $payment->status,
                        'subscription_id' =>
                        $payment->subscription?->id,
                    ]
                );

                break;


            /*
             * ============================================================
             * PAYMENT INTENT SUCCEEDED
             * ============================================================
             *
             * This is important because in your latest test Stripe sent:
             *
             * payment_intent.succeeded
             *
             * but did NOT send checkout.session.completed.
             */
            case 'payment_intent.succeeded':

                $paymentIntent = $event->data->object;

                $paymentId =
                    $paymentIntent->metadata->payment_id ?? null;

                logger()->info(
                    'Stripe payment intent succeeded',
                    [
                        'payment_intent_id' =>
                        $paymentIntent->id,
                        'payment_id' => $paymentId,
                    ]
                );

                if (!$paymentId) {
                    logger()->warning(
                        'Stripe payment intent has no payment_id metadata',
                        [
                            'payment_intent_id' =>
                            $paymentIntent->id,
                        ]
                    );

                    break;
                }

                $payment = Payment::query()->find($paymentId);

                if (!$payment) {
                    logger()->warning(
                        'Payment not found for Stripe payment intent',
                        [
                            'payment_id' => $paymentId,
                            'payment_intent_id' =>
                            $paymentIntent->id,
                        ]
                    );

                    break;
                }

                /*
                 * pay() already protects us from paying an already-paid
                 * payment because it only accepts PENDING payments.
                 */
                if ($payment->status->value === 'pending') {

                    $payment = $this->paymentLifeSycle->pay($payment);

                    $payment->update([
                        'gateway' => 'stripe',
                        'transaction_id' =>
                        $paymentIntent->id,
                        'metadata' => array_merge(
                            $payment->metadata ?? [],
                            [
                                'stripe_payment_intent_id' =>
                                $paymentIntent->id,
                            ]
                        ),
                    ]);

                    logger()->info(
                        'Stripe payment intent processed successfully',
                        [
                            'payment_id' => $payment->id,
                            'subscription_id' =>
                            $payment->subscription?->id,
                            'start_date' =>
                            $payment->subscription?->start_date,
                            'end_date' =>
                            $payment->subscription?->end_date,
                        ]
                    );
                } else {
                    logger()->info(
                        'Stripe payment intent ignored because payment is not pending',
                        [
                            'payment_id' => $payment->id,
                            'payment_status' => $payment->status,
                        ]
                    );
                }

                break;


            /*
             * ============================================================
             * PAYMENT INTENT FAILED
             * ============================================================
             */
            case 'payment_intent.payment_failed':

                $paymentIntent = $event->data->object;

                $paymentId =
                    $paymentIntent->metadata->payment_id ?? null;

                logger()->warning(
                    'Stripe payment failed',
                    [
                        'payment_intent_id' =>
                        $paymentIntent->id,
                        'payment_id' => $paymentId,
                        'reason' =>
                        $paymentIntent
                            ->last_payment_error
                            ->message ?? null,
                    ]
                );

                if ($paymentId) {

                    $payment = Payment::query()->find($paymentId);

                    if (
                        $payment &&
                        $payment->status->value === 'pending'
                    ) {
                        $this->paymentLifeSycle->fail(
                            $payment,
                            $paymentIntent
                                ->last_payment_error
                                ->message ?? null
                        );
                    }
                }

                break;
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
