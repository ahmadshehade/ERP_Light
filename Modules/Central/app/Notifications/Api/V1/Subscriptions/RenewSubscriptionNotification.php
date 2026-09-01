<?php

namespace Modules\Central\Notifications\Api\V1\Subscriptions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class RenewSubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120, 300];

    public int $timeout = 120;

    public function __construct(
        public int $subscriptionId,
        public string $companyName,
        public string $planName,
        public float $price,
        public string $interval,
        public string $checkoutUrl,
    ) {}

    /**
     * Notification channels.
     */
    public function via($notifiable): array
    {
        return [
            'database',
            'mail',
        ];
    }

    /**
     * Mail notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(
                '🔄 Subscription Renewal - ' .
                    $this->companyName
            )
            ->greeting(
                '👋 Hello ' .
                    ($notifiable->name ?? 'User') .
                    '!'
            )
            ->line(
                'Your subscription for **' .
                    $this->companyName .
                    '** is ready for renewal.'
            )
            ->line(
                '📋 **Renewal Details:**'
            )
            ->line(
                '• **Plan:** ' .
                    $this->planName
            )
            ->line(
                '• **Price:** $' .
                    number_format($this->price, 2) .
                    ' / ' .
                    $this->interval
            )
            ->action(
                '💳 Complete Payment',
                $this->checkoutUrl
            )
            ->line(
                'Please complete the payment to renew your subscription.'
            )
            ->line(
                'Thank you for using our application! 🚀'
            );
    }

    /**
     * Database notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'subscription_renewal',

            'icon' => '🔄',

            'title' => [
                'ar' => 'تجديد الاشتراك',
                'en' => 'Subscription Renewal',
            ],

            'message' => [
                'ar' => sprintf(
                    'يرجى إتمام الدفع لتجديد اشتراكك في خطة %s للشركة %s.',
                    $this->planName,
                    $this->companyName
                ),

                'en' => sprintf(
                    'Please complete the payment to renew your %s subscription for %s.',
                    $this->planName,
                    $this->companyName
                ),
            ],

            'subscription_id' => $this->subscriptionId,

            'company_name' => $this->companyName,

            'plan_name' => $this->planName,

            'price' => number_format(
                $this->price,
                2
            ),

            'currency' => 'USD',

            'interval' => $this->interval,

            'checkout_url' => $this->checkoutUrl,

            'view_url' => url(
                "/api/v1/central/subscriptions/{$this->subscriptionId}"
            ),

            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Called when the queued notification fails permanently.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical(
            '❌ Renew subscription notification failed permanently',
            [
                'subscription_id' => $this->subscriptionId,
                'error' => $exception->getMessage(),
            ]
        );
    }
}
