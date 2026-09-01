<?php

namespace Modules\Central\Notifications\Api\V1\SubscriptionPlans;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeActiveSubscriptionPlanNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $planNameEn,
        public string $planNameAr,
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
            ->subject('Subscription Plan Deactivated')
            ->greeting('Hello!')
            ->line('A subscription plan has been deactivated.')
            ->line("Plan: {$this->planNameEn}")
            ->line("Plan (Arabic): {$this->planNameAr}")
            ->line('Status: Inactive')
            ->line('This subscription plan is no longer available for new subscriptions.')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'plan_name_en' => $this->planNameEn,
            'plan_name_ar' => $this->planNameAr,
            'is_active' => $this->isActive,
        ];
    }
}
