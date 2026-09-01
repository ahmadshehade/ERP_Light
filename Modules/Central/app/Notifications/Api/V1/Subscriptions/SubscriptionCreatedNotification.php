<?php

namespace Modules\Central\Notifications\Api\V1\Subscriptions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Modules\Central\Models\Subscription;
use RuntimeException;
use Throwable;

class SubscriptionCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [10, 30, 60, 120, 300];

    public int $timeout = 120;

    public function __construct(
        public int $subscriptionId,
        public string $checkoutUrl,
    ) {}

    /**
     * Notification channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        $email = trim((string) ($notifiable->email ?? ''));

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $channels[] = 'mail';

            Log::info('📧 Mail channel added', [
                'user_id' => $notifiable->id ?? null,
                'email' => $email,
            ]);
        } else {
            Log::warning('⚠️ Invalid email skipped', [
                'user_id' => $notifiable->id ?? null,
                'email' => $email,
            ]);
        }

        return $channels;
    }

    /**
     * Mail notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subscription = Subscription::with([
            'company',
            'price.plan',
        ])->findOrFail($this->subscriptionId);

        $company = $subscription->company;
        $price = $subscription->price;
        $plan = $price?->plan;

        if (!$company || !$price || !$plan) {
            throw new RuntimeException(
                'Subscription email data is incomplete.'
            );
        }

        $companyName = $company->getTranslation('name', 'en');
        $planName = $plan->getTranslation('name', 'en');
        $interval = $price->interval?->value ?? 'N/A';

        Log::info('📧 Preparing subscription email', [
            'subscription_id' => $subscription->id,
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email ?? null,
            'company' => $companyName,
            'plan' => $planName,
            'price' => $price->price,
            'interval' => $interval,
            'status' => $subscription->status->value,
        ]);

        return (new MailMessage)
            ->subject(
                '🎉 Subscription Created - ' . $companyName
            )
            ->greeting(
                '👋 Hello ' . ($notifiable->name ?? 'User') . '!'
            )
            ->line(
                'Your subscription for **' .
                    $companyName .
                    '** has been created successfully!'
            )
            ->line('📋 **Subscription Details:**')
            ->line(
                '• **Plan:** ' . $planName
            )
            ->line(
                '• **Price:** $' .
                    number_format($price->price, 2) .
                    ' / ' .
                    $interval
            )
            ->line(
                '• **Trial End:** ' .
                    (
                        $subscription->trial_end_date
                        ? $subscription->trial_end_date->format('d F Y')
                        : 'No trial'
                    )
            )
            ->line(
                '• **Status:** ' .
                    ucfirst($subscription->status->value)
            )
            ->action(
                '💳 Pay Now',
                $this->checkoutUrl
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
        $subscription = Subscription::with([
            'company',
            'price.plan',
        ])->findOrFail($this->subscriptionId);

        $company = $subscription->company;
        $price = $subscription->price;
        $plan = $price?->plan;

        if (!$company || !$price || !$plan) {
            throw new RuntimeException(
                'Subscription database notification data is incomplete.'
            );
        }

        return [
            'type' => 'subscription_created',

            'icon' => '🎉',

            'title' => [
                'ar' => 'تم إنشاء الاشتراك',
                'en' => 'Subscription Created',
            ],

            'message' => [
                'ar' => sprintf(
                    'تم إنشاء اشتراكك في خطة %s للشركة %s بنجاح.',
                    $plan->getTranslation('name', 'ar'),
                    $company->getTranslation('name', 'ar')
                ),

                'en' => sprintf(
                    'Your %s subscription for %s has been created successfully.',
                    $plan->getTranslation('name', 'en'),
                    $company->getTranslation('name', 'en')
                ),
            ],

            'subscription_id' => $subscription->id,

            'company_id' => $subscription->company_id,

            'company_name' => [
                'ar' => $company->getTranslation('name', 'ar'),
                'en' => $company->getTranslation('name', 'en'),
            ],

            'plan_name' => [
                'ar' => $plan->getTranslation('name', 'ar'),
                'en' => $plan->getTranslation('name', 'en'),
            ],

            'price' => number_format(
                $price->price,
                2
            ),

            'currency' => 'USD',

            'interval' => $price->interval?->value,

            'status' => $subscription->status->value,

            'checkout_url' => $this->checkoutUrl,

            'view_url' => url(
                "/api/v1/central/subscriptions/{$subscription->id}"
            ),

            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Called when the queued notification
     * fails after all retries.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical(
            '❌ Subscription created notification failed permanently',
            [
                'subscription_id' => $this->subscriptionId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]
        );
    }
}
