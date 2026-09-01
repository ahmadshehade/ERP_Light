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

class UpdateSubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public $backoff = [
        10,
        30,
        60,
        120,
        300,
    ];

    public $timeout = 120;

    public function __construct(
        public int $subscriptionId,
        public string $checkoutUrl,
        public array $changes = [],
        public ?string $oldPlanName = null,
        public ?string $newPlanName = null,
        public ?float $oldPrice = null,
        public ?float $newPrice = null,
    ) {}

    /**
     * Get the notification delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = [
            'database',
        ];

        if (
            $notifiable &&
            isset($notifiable->email)
        ) {
            $email = trim($notifiable->email);

            if (
                filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                $channels[] = 'mail';

                Log::info('✅ Mail channel added', [
                    'user_id' => $notifiable->id ?? null,
                    'email' => $email,
                ]);
            }
        }

        return $channels;
    }

    /**
     * Get the mail representation.
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
                'Subscription notification data is incomplete.'
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

        $changesMessage = '';

        if (!empty($this->changes)) {
            $changesList = [];

            foreach ($this->changes as $field => $change) {
                $changesList[] =
                    ucfirst($field) . ': ' . $change;
            }

            $changesMessage = implode(
                "\n",
                $changesList
            );
        }

        if (
            $this->oldPlanName !== null &&
            $this->newPlanName !== null
        ) {
            $changesMessage .=
                ($changesMessage ? "\n" : '') .
                'Plan: ' .
                $this->oldPlanName .
                ' → ' .
                $this->newPlanName;
        }

        if (
            $this->oldPrice !== null &&
            $this->newPrice !== null
        ) {
            $changesMessage .=
                ($changesMessage ? "\n" : '') .
                'Price: $' .
                number_format($this->oldPrice, 2) .
                ' → $' .
                number_format($this->newPrice, 2);
        }

        Log::info('📧 Preparing subscription update email', [
            'subscription_id' => $subscription->id,
            'email' => $notifiable->email,
            'company' => $companyName,
            'plan' => $planName,
            'old_plan' => $this->oldPlanName,
            'new_plan' => $this->newPlanName,
            'old_price' => $this->oldPrice,
            'new_price' => $this->newPrice,
        ]);

        $mail = (new MailMessage)
            ->subject(
                '🔄 Subscription Updated - ' .
                    $companyName
            )
            ->greeting(
                '👋 Hello ' .
                    ($notifiable->name ?? 'User') .
                    '!'
            )
            ->line(
                'Your subscription for **' .
                    $companyName .
                    '** has been updated successfully.'
            )
            ->line('📋 Updated Subscription Details:')
            ->line(
                'Plan: ' . $planName
            )
            ->line(
                'Price: $' .
                    number_format($price->price, 2) .
                    ' / ' .
                    ($price->interval?->value ?? 'N/A')
            )
            ->line(
                'Status: ' .
                    ucfirst(
                        $subscription->status->value
                    )
            );

        if ($changesMessage !== '') {
            $mail->line('🔄 Changes:');

            foreach (
                explode("\n", $changesMessage)
                as $change
            ) {
                $mail->line($change);
            }
        }

        $mail
            ->action(
                '💳 Pay Now',
                $this->checkoutUrl
            )
            ->line(
                'Thank you for using our application! 🚀'
            );

        return $mail;
    }

    /**
     * Get the array representation.
     */
    public function toArray($notifiable): array
    {
        try {

            $subscription = Subscription::with([
                'company',
                'price.plan',
            ])->findOrFail(
                $this->subscriptionId
            );

            $company = $subscription->company;
            $price = $subscription->price;
            $plan = $price?->plan;

            if (!$company || !$price || !$plan) {
                throw new RuntimeException(
                    'Subscription notification data is incomplete.'
                );
            }

            return [
                'type' => 'subscription_updated',

                'icon' => '🔄',

                'title' => [
                    'ar' => 'تم تحديث الاشتراك',
                    'en' => 'Subscription Updated',
                ],

                'message' => [
                    'ar' => sprintf(
                        'تم تحديث اشتراكك في خطة %s للشركة %s بنجاح.',
                        $plan->getTranslation(
                            'name',
                            'ar'
                        ),
                        $company->getTranslation(
                            'name',
                            'ar'
                        )
                    ),

                    'en' => sprintf(
                        'Your %s subscription for %s has been updated successfully.',
                        $plan->getTranslation(
                            'name',
                            'en'
                        ),
                        $company->getTranslation(
                            'name',
                            'en'
                        )
                    ),
                ],

                'subscription_id' =>
                $subscription->id,

                'company_id' =>
                $subscription->company_id,

                'company_name' => [
                    'ar' => $company->getTranslation(
                        'name',
                        'ar'
                    ),
                    'en' => $company->getTranslation(
                        'name',
                        'en'
                    ),
                ],

                'plan_name' => [
                    'ar' => $plan->getTranslation(
                        'name',
                        'ar'
                    ),
                    'en' => $plan->getTranslation(
                        'name',
                        'en'
                    ),
                ],

                'price' =>
                number_format(
                    $price->price,
                    2
                ),

                'currency' => 'USD',

                'interval' =>
                $price->interval?->value,

                'status' =>
                $subscription->status->value,

                'changes' =>
                $this->changes,

                'old_plan_name' =>
                $this->oldPlanName,

                'new_plan_name' =>
                $this->newPlanName,

                'old_price' =>
                $this->oldPrice !== null
                    ? number_format(
                        $this->oldPrice,
                        2
                    )
                    : null,

                'new_price' =>
                $this->newPrice !== null
                    ? number_format(
                        $this->newPrice,
                        2
                    )
                    : null,

                'checkout_url' =>
                $this->checkoutUrl,

                'view_url' =>
                url(
                    "/api/v1/central/subscriptions/{$subscription->id}"
                ),

                'created_at' =>
                now()->toDateTimeString(),
            ];
        } catch (Throwable $e) {

            Log::error(
                '❌ Failed to prepare subscription update notification',
                [
                    'subscription_id' =>
                    $this->subscriptionId,

                    'error' =>
                    $e->getMessage(),
                ]
            );

            return [
                'type' =>
                'subscription_updated',

                'subscription_id' =>
                $this->subscriptionId,

                'checkout_url' =>
                $this->checkoutUrl,

                'changes' =>
                $this->changes,

                'created_at' =>
                now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Handle a failed notification job.
     */
    public function failed(Throwable $exception): void
    {
        Log::critical(
            '❌ UpdateSubscriptionNotification failed after all retries',
            [
                'subscription_id' =>
                $this->subscriptionId,

                'error' =>
                $exception->getMessage(),

                'trace' =>
                $exception->getTraceAsString(),
            ]
        );
    }
}
