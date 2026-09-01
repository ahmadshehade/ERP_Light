<?php

namespace Modules\Central\Notifications\Api\V1\SupscriptionPrices;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CreateSubscriptionPriceNotify extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $priceId,
        public string $planNameEn,
        public string $planNameAr,
        public float $price,
        public string $interval,
        public bool $hasTrial,
        public int $trialDays,
        public bool $isActive
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return [
            'mail',
            'database',
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Subscription Price Created')
            ->greeting('Hello!')
            ->line('A new subscription price has been created successfully.')
            ->line("Plan English: {$this->planNameEn}")
            ->line("Plan Arabic: {$this->planNameAr}")
            ->line("Price: {$this->price}")
            ->line("Interval: {$this->interval}")
            ->line(
                'Trial: ' .
                    ($this->hasTrial
                        ? "Yes ({$this->trialDays} days)"
                        : 'No')
            )
            ->line(
                'Status: ' .
                    ($this->isActive
                        ? 'Active'
                        : 'Inactive')
            )
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'price_id' => $this->priceId,

            'plan_name_en' => $this->planNameEn,

            'plan_name_ar' => $this->planNameAr,

            'price' => $this->price,

            'interval' => $this->interval,

            'has_trial' => $this->hasTrial,

            'trial_days' => $this->trialDays,

            'is_active' => $this->isActive,
        ];
    }
}
