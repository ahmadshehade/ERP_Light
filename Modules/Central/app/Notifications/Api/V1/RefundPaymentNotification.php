<?php

namespace Modules\Central\Notifications\Api\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundPaymentNotification extends Notification implements ShouldQueue
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
            ->subject('Payment Refunded')
            ->greeting('Hello,')
            ->line(
                "Your payment for {$this->companyName} has been refunded successfully."
            )
            ->line("Plan: {$this->planName}")
            ->line(
                'Refunded amount: $' .
                    number_format($this->price, 2) .
                    " / {$this->interval}"
            )
            ->line(
                'Your subscription has been cancelled because the payment was refunded.'
            )
            ->line(
                'The refunded amount will be returned according to your payment provider processing time.'
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
