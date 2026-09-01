<?php

namespace Modules\Tenant\Notifications\Tasks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeleteTaskNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title_en,
        public string $title_ar,
        public int $task_id,
        public string $company_name_en,
        public string $company_name_ar,
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
            ->subject(
                "Task Deleted - {$this->company_name_en}"
            )
            ->greeting(
                'Hello ' . ($notifiable->name ?? '') . ','
            )
            ->line(
                "A task has been deleted from {$this->company_name_en}."
            )
            ->line(
                "Task: {$this->title_en}"
            )
            ->line(
                "The task is no longer active and has been moved to the deleted items."
            )
            ->action(
                'View Deleted Tasks',
                url('/api/v1/tasks/trashed')
            )
            ->line(
                'If this action was not expected, please contact your company administrator.'
            )
            ->line(
                "Thank you for using {$this->company_name_en}."
            );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => self::class,

            'task_id' => $this->task_id,

            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,

            'company_name_en' => $this->company_name_en,
            'company_name_ar' => $this->company_name_ar,

            'message_en' => "Task \"{$this->title_en}\" has been deleted.",
            'message_ar' => "تم حذف المهمة \"{$this->title_ar}\".",
        ];
    }
}
