<?php

namespace Modules\Tenant\Events\ProjectTeams;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemoveProjectFromTeamsEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $projectId,
        public array $teamIds,
        public string $name_en,
        public string $name_ar,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("project-teams-remove.{$this->projectId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'project.removed-from-teams';
    }

    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->projectId,
            'team_ids' => $this->teamIds,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
        ];
    }
}
