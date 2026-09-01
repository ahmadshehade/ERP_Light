<?php

namespace Modules\Central\Notifications\Api\V1\Subscriptions;

use Carbon\Carbon;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotifyExpiringSubscription extends Notification
{
    use Queueable;

    public function __construct(
        public int $subscriptionId,
        public string $companyName,
        public string $planName,
        public Carbon $end_date,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Subscription Is Expiring Soon')
            ->greeting('Hello,')
            ->line(
                "Your subscription for {$this->companyName} is expiring soon."
            )
            ->line("Plan: {$this->planName}")
            ->line(
                'Expiration date: ' .
                    $this->end_date->format('Y-m-d H:i')
            )
            ->line(
                'Please renew your subscription before it expires.'
            )
            ->action(
                'Renew Subscription',
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
            'end_date' => $this->end_date->toDateTimeString(),
            'type' => static::class,
        ];
    }
}
