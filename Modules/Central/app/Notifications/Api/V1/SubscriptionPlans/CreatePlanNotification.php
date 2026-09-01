<?php

namespace Modules\Central\Notifications\Api\V1\SubscriptionPlans;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CreatePlanNotification extends Notification implements ShouldQueue
{
    use Queueable;


    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $nameAr,
        public string $descriptionAr,
        public string $nameEn,
        public string $descriptionEn
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
            ->line('New Plan Has Been Actived')
            ->line('Name Arabic: ' . $this->nameAr)
            ->line('Name English: ' . $this->nameEn)
            ->line('description Arabic: ' . $this->descriptionAr)
            ->line('description English: ' . $this->descriptionEn)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'nameAr' => $this->nameAr,
            'nameEn' => $this->nameEn,
            'descriptionEn' => $this->descriptionEn,
            'descriptionAr' => $this->descriptionAr,
            'static' => self::class
        ];
    }
}
