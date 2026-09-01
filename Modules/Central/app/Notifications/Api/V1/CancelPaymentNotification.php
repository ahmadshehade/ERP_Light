<?php

namespace Modules\Central\Notifications\Api\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CancelPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $subscriptionId,
        public string $companyName,
        public string $planName,
        public float $price,
        public string $interval,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Cancelled')
            ->greeting('Hello,')
            ->line(
                "The payment for your subscription at {$this->companyName} has been cancelled."
            )
            ->line("Plan: {$this->planName}")
            ->line(
                'Amount: $' .
                    number_format($this->price, 2) .
                    " / {$this->interval}"
            )
            ->line(
                'Your subscription has been cancelled and will not be activated.'
            )
            ->line(
                'If you cancelled this payment by mistake, you can start a new payment process.'
            )
            ->action(
                'View Subscriptions',
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
            'price' => $this->price,
            'interval' => $this->interval,
            'type' => static::class,
        ];
    }
}
