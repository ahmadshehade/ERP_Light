<?php

namespace Modules\Tenant\Notifications\Tasks;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Tenant\Models\Task;

class CancelTaskNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Task $task,
        public string $companyNameEn,
        public string $companyNameAr,
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
                "Task Cancelled - {$this->companyNameEn}"
            )
            ->greeting(
                'Hello ' . ($notifiable->name ?? '')
            )
            ->line(
                'A task assigned to your team has been cancelled.'
            )
            ->line(
                'Task: ' .
                    $this->task->getTranslation('title', 'en')
            )
            ->line(
                'Status: ' .
                    $this->task->status->value
            )
            ->line(
                'Priority: ' .
                    $this->task->priority->value
            )
            ->line(
                'Start Date: ' .
                    $this->task->start_date?->format('Y-m-d H:i')
            )
            ->line(
                'Due Date: ' .
                    $this->task->due_date?->format('Y-m-d H:i')
            )
            ->action(
                'View Task',
                url("/api/v1/tasks/{$this->task->id}")
            )
            ->line(
                'Thank you for using ' .
                    $this->companyNameEn .
                    '.'
            );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => self::class,

            'project_id' => $this->task->project_id,

            'task_id' => $this->task->id,

            'title_en' => $this->task->getTranslation(
                'title',
                'en'
            ),

            'title_ar' => $this->task->getTranslation(
                'title',
                'ar'
            ),

            'description_en' => $this->task->getTranslation(
                'description',
                'en'
            ),

            'description_ar' => $this->task->getTranslation(
                'description',
                'ar'
            ),

            'is_active' => $this->task->is_active,

            'status' => $this->task->status->value,

            'priority' => $this->task->priority->value,

            'start_date' => $this->task->start_date,

            'due_date' => $this->task->due_date,

            'company_name_en' => $this->companyNameEn,

            'company_name_ar' => $this->companyNameAr,
        ];
    }
}
