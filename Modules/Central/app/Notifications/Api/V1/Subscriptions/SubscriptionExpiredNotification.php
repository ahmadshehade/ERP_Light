<?php

namespace Modules\Central\Notifications\Api\V1\Subscriptions;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $subscriptionId,
        public string $companyName,
        public string $planName,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Subscription Has Expired')
            ->greeting('Hello,')
            ->line(
                "Your subscription for {$this->companyName} has expired."
            )
            ->line("Plan: {$this->planName}")
            ->line(
                'Your subscription is no longer active.'
            )
            ->action(
                'Renew Subscription',
                config('app.frontend_url') . '/subscriptions'
            )
            ->line(
                'Please renew your subscription to continue using our application.'
            )
            ->salutation('Thank you, The Support Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'company_name' => $this->companyName,
            'plan_name' => $this->planName,
            'type' => static::class,
        ];
    }
}
