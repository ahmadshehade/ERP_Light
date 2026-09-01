<?php

namespace Modules\Central\Notifications\Api\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FailedPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $subscriptionId,
        public string $companyName,
        public string $planName,
        public float $price,
        public string $interval,
        public ?string $reason = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment Failed')
            ->greeting('Hello,')
            ->line(
                'We were unable to process the payment for your subscription.'
            )
            ->line("Company: {$this->companyName}")
            ->line("Plan: {$this->planName}")
            ->line(
                'Amount: $'
                    . number_format($this->price, 2)
                    . " / {$this->interval}"
            )
            ->line(
                'Please check your payment details and try again.'
            )
            ->line(
                'Your subscription has not been activated because the payment was unsuccessful.'
            )
            ->line(
                'If you believe this is an error, please contact our support team.'
            )
            ->line(
                'Reason: ' . ($this->reason ?? 'Unknown payment failure')
            )
            ->salutation('Thank you, The Support Team');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'subscription_id' => $this->subscriptionId,
            'company_name' => $this->companyName,
            'plan_name' => $this->planName,
            'price' => $this->price,
            'interval' => $this->interval,
        ];
    }
}
