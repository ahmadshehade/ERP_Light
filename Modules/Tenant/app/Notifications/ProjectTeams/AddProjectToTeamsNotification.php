<?php

namespace Modules\Tenant\Notifications\ProjectTeams;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddProjectToTeamsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $projectId,
        public string $name_en,
        public string $name_ar,
        public array $teams,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('New Project Assigned')
            ->greeting('Hello ' . ($notifiable->name ?? '') . ',')
            ->line(
                "The project '{$this->name_en}' has been added to your team."
            );

        foreach ($this->teams as $team) {
            $message->line("Team: {$team['name_en']}");
        }

        return $message
            ->action(
                'View Project',
                url("/projects/{$this->projectId}")
            )
            ->line('You can now start working on this project.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'project_added_to_teams',

            'project_id' => $this->projectId,

            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,

            'teams' => $this->teams,
        ];
    }
}

