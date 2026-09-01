<?php

namespace Modules\Tenant\Events\ProjectTeams;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AddProjectToTeamsEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $projectId,
        public string $name_en,
        public string $name_ar,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("project-teams.{$this->projectId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'project.added-to-teams';
    }

    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->projectId,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
        ];
    }
}
