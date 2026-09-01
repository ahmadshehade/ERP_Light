<?php

namespace Modules\Tenant\Notifications\Projects;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Tenant\Enum\ProjectPriority;
use Modules\Tenant\Enum\ProjectStatus;

class RestoreProjectNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $name_en,
        public string $name_ar,
        public string $description_en,
        public string $description_ar,
        public ProjectStatus $status,
        public ProjectPriority $priority,
        public string $company_name_en,
        public string $company_name_ar,
        public bool $is_active,
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
        return (new MailMessage)
            ->subject('Project Restored - ' . $this->name_en)

            ->greeting(
                'Hello ' . ($notifiable->user?->name ?? 'there') . ','
            )

            ->line(
                'A project has been successfully restored and is available again.'
            )

            ->line('### Restored Project')

            ->line('**Project:** ' . $this->name_en)

            ->line('**Description:** ' . $this->description_en)

            ->line('**Status:** ' . $this->status->value)

            ->line('**Priority:** ' . $this->priority->value)

            ->line(
                '**Project Status:** ' .
                    ($this->is_active ? 'Active' : 'Inactive')
            )

            ->line('**Company:** ' . $this->company_name_en)

            ->line(
                'The project has been restored successfully and is now available in your projects.'
            )

            ->line('Thank you for using our platform.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'project.restored',

            'name' => [
                'en' => $this->name_en,
                'ar' => $this->name_ar,
            ],

            'description' => [
                'en' => $this->description_en,
                'ar' => $this->description_ar,
            ],

            'status' => $this->status->value,

            'priority' => $this->priority->value,

            'company_name' => [
                'en' => $this->company_name_en,
                'ar' => $this->company_name_ar,
            ],

            'is_active' => $this->is_active,

            'notification' => self::class,
        ];
    }
}
