<?php

namespace Modules\Tenant\Notifications\Tasks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdateTaskNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $projectId,
        public int $taskId,

        public string $title_en,
        public string $title_ar,

        public string $description_en,
        public string $description_ar,

        public bool $is_active,

        public string $status,
        public string $priority,

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
                "Task Updated - {$this->title_en}"
            )
            ->greeting(
                "Hello " . ($notifiable->name ?? '') . ","
            )
            ->line(
                "A task has been updated in {$this->company_name_en}."
            )
            ->line(
                "Task: {$this->title_en}"
            )
            ->line(
                "Description: {$this->description_en}"
            )
            ->line(
                "Status: {$this->status}"
            )
            ->line(
                "Priority: {$this->priority}"
            )
            ->line(
                "Active: " . ($this->is_active ? 'Yes' : 'No')
            )
            ->action(
                'View Task',
                url("/api/v1/tasks/{$this->taskId}")
            )
            ->line(
                "The task information has been updated successfully."
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

            'project_id' => $this->projectId,
            'task_id' => $this->taskId,

            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,

            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,

            'is_active' => $this->is_active,

            'status' => $this->status,
            'priority' => $this->priority,

            'company_name_en' => $this->company_name_en,
            'company_name_ar' => $this->company_name_ar,
        ];
    }
}
