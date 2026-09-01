<?php

namespace Modules\Central\Notifications\Api\V1\Subscriptions;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TrialExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $subscriptionId,
        public string $companyName,
        public string $planName,
        public string $trialEndDate,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Free Trial Has Expired')
            ->greeting('Hello,')
            ->line(
                "Your free trial for {$this->companyName} has expired."
            )
            ->line(
                "Plan: {$this->planName}"
            )
            ->line(
                "Trial expiration date: {$this->trialEndDate}"
            )
            ->line(
                'Your subscription is no longer active.'
            )
            ->line(
                'Subscribe now to continue using our application.'
            )
            ->action(
                'Choose a Subscription',
                config('app.frontend_url') . '/subscriptions'
            )
            ->line(
                'Thank you for using our application!'
            );
    }

    public function toArray($notifiable): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'company_name' => $this->companyName,
            'plan_name' => $this->planName,
            'trial_end_date' => $this->trialEndDate,
            'type' => static::class,
        ];
    }
}
