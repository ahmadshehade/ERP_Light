<?php

namespace Modules\Central\Notifications\Api\V1;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SuccessPaymentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $subscriptionId,
        public string $companyName,
        public string $planName,
        public float $priceValue,
        public string $interval,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        try {
            return (new MailMessage)
                ->subject('Subscription Paid Successfully')
                ->greeting('Hello!')
                ->line('Your subscription has been paid successfully.')
                ->line("Company: {$this->companyName}")
                ->line("Plan: {$this->planName}")
                ->line("Price: {$this->priceValue} USD")
                ->line("Interval: {$this->interval}")
                ->line('Thank you for using our application.');
        } catch (\Exception $e) {
            Log::error('SuccessPaymentNotification::toMail', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'subscription_renewed',
            'subscription_id' => $this->subscriptionId,
            'company_name' => $this->companyName,
            'plan_name' => $this->planName,
            'price' => $this->priceValue,
            'interval' => $this->interval,
        ];
    }
}
