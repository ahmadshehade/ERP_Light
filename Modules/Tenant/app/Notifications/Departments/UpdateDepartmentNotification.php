<?php

namespace Modules\Tenant\Notifications\Departments;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UpdateDepartmentNotification extends Notification implements  ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $departmentId,
        public string $name_en,
        public string $name_ar,
        public string  $description_en,
        public string $description_ar,
        public bool $is_active
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

        return (new MailMessage)
            ->subject('New Department updated')

            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')

            ->line('A new department has been successfully updated in your organization.')

            ->line('### Department Information')

            ->line('**Department Name (English):** ' . $this->name_en)
            ->line('**Department Name (Arabic):** ' . $this->name_ar)

            ->line('**Status:** ' . $status)

            ->line('### Description')

            ->line('**Arabic:** ' . ($this->description_ar ?: 'No description provided.'))
            ->line('**English:** ' . ($this->description_en ?: 'No description provided.'))

            ->action(
                'View Department',
                url('/departments/' . $this->departmentId)
            )

            ->line('The department was updated successfully and is now available in your organization.')

            ->salutation('Best regards,')
            ->salutation(config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'department_id' => $this->departmentId,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'is_active' => $this->is_active
        ];
    }
}
