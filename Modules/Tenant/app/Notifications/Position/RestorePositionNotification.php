<?php

namespace Modules\Tenant\Notifications\Position;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestorePositionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $positionId,
        public string $name_en,
        public string $name_ar,
        public ?string $description_en,
        public ?string $description_ar,
        public bool $is_active,
        public string $company_name_en,
        public string $company_name_ar,
        public string $uuid,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $status = $this->is_active
            ? 'Active'
            : 'Inactive';

        $mail = (new MailMessage)
            ->subject('Position Restored')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A position has been restored in your company.')
            ->line('Company: ' . $this->company_name_en)
            ->line('Position: ' . $this->name_en)
            ->line('Position (Arabic): ' . $this->name_ar)
            ->line('Position UUID: ' . $this->uuid);

        if ($this->description_en) {
            $mail->line(
                'Description: ' . $this->description_en
            );
        }

        if ($this->description_ar) {
            $mail->line(
                'Description (Arabic): ' . $this->description_ar
            );
        }

        $mail->line('Status: ' . $status);

        return $mail->salutation(
            'Regards, ' . config('app.name')
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'position_id' => $this->positionId,

            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,

            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,

            'is_active' => $this->is_active,
            'uuid' => $this->uuid,

            'company_name_en' => $this->company_name_en,
            'company_name_ar' => $this->company_name_ar,

            'type' => self::class,
        ];
    }
}
