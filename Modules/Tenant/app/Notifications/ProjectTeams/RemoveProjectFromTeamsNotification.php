<?php

namespace Modules\Tenant\Notifications\ProjectTeams;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RemoveProjectFromTeamsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $projectId,
        public string $name_en,
        public string $name_ar,
        public array $teams,
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
            ->subject('Project removed from your teams')
            ->greeting('Hello ' . ($notifiable->name ?? '') . ',')
            ->line(
                "The project \"{$this->name_en}\" has been removed from the following team(s):"
            )
            ->line(
                collect($this->teams)
                    ->pluck('name_en')
                    ->implode(', ')
            )
            ->action(
                'View Project',
                url("/projects/{$this->projectId}")
            )
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'project_id' => $this->projectId,

            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,

            'teams' => $this->teams,
        ];
    }
}

