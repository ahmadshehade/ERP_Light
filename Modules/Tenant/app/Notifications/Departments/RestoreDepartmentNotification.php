<?php

namespace Modules\Tenant\Notifications\Departments;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestoreDepartmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $departmentId,
        public string $name_en,
        public string $name_ar,
        public string $description_ar,
        public string $description_en,
        public bool $is_active
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Department Restored Successfully')

            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')

            ->line(
                'A department has been successfully restored and is now available again in your organization.'
            )

            ->line('### Department Information')

            ->line('**Department Name (English):** ' . $this->name_en)

            ->line('**Department Name (Arabic):** ' . $this->name_ar)

            ->line(
                '**Status:** ' . ($this->is_active ? 'Active' : 'Inactive')
            )

            ->line('### Description')

            ->line(
                '**English:** ' .
                    ($this->description_en ?: 'No description provided.')
            )

            ->line(
                '**Arabic:** ' .
                    ($this->description_ar ?: 'No description provided.')
            )

            ->action(
                'View Department',
                url('/departments/' . $this->departmentId)
            )

            ->line(
                'The department has been restored successfully and is available for use again.'
            )

            ->salutation('Best regards,')
            ->salutation(config('app.name'));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Department Restored',

            'message' => "The department {$this->name_en} has been restored successfully.",

            'department_id' => $this->departmentId,

            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,

            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,

            'is_active' => $this->is_active,

            'created_at' => now(),

            'type' => self::class,
        ];
    }
}
