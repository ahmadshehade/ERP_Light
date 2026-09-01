<?php

namespace Modules\Central\Notifications\Api\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetryPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $subscriptionId,
        public string $companyName,
        public string $planName,
        public string $checkoutUrl,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Retry Required')
            ->greeting('Hello,')
            ->line(
                "The previous payment for your {$this->companyName} subscription was unsuccessful."
            )
            ->line("Plan: {$this->planName}")
            ->line(
                'A new payment session has been created. Please complete your payment to activate your subscription.'
            )
            ->action(
                'Complete Payment',
                $this->checkoutUrl
            )
            ->line(
                'If you have already completed the payment, you can ignore this email.'
            )
            ->salutation('Thank you, The Support Team');
    }

    public function toArray($notifiable): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'company_name' => $this->companyName,
            'plan_name' => $this->planName,
            'checkout_url' => $this->checkoutUrl,
            'type' => static::class,
        ];
    }
}
