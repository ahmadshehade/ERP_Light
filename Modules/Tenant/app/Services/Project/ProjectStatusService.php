<?php

namespace Modules\Tenant\Services\Project;

use Illuminate\Support\Facades\DB;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Models\Project;

class ProjectStatusService
{
    public function change(
        Project $project,
        ProjectStatus $newStatus
    ): Project {

        return DB::connection('tenant')->transaction(function () use (
            $project,
            $newStatus
        ) {

            $currentStatus = $project->status;
            if ($currentStatus === $newStatus) {
                return $project;
            }
            if (! $this->canTransition($currentStatus, $newStatus)) {
                throw new \DomainException(
                    "Cannot change project status from {$currentStatus->value} to {$newStatus->value}."
                );
            }

            $project->status = $newStatus;

            match ($newStatus) {
                ProjectStatus::Planned => $this->setPlanned($project),
                ProjectStatus::InProgress => $this->setInProgress($project),
                ProjectStatus::Completed => $this->setCompleted($project),
                ProjectStatus::OnHold => $this->setOnHold($project),
                ProjectStatus::Cancelled => $this->setCancelled($project),
            };
            $project->save();
            return $project->fresh();
        });
    }

    protected function canTransition(
        ProjectStatus $currentStatus,
        ProjectStatus $newStatus
    ): bool {

        return match ($currentStatus) {

            ProjectStatus::Planned => in_array(
                $newStatus,
                [
                    ProjectStatus::InProgress,
                    ProjectStatus::Cancelled,
                ],
                true
            ),

            ProjectStatus::InProgress => in_array(
                $newStatus,
                [
                    ProjectStatus::Completed,
                    ProjectStatus::OnHold,
                    ProjectStatus::Cancelled,
                ],
                true
            ),

            ProjectStatus::OnHold => in_array(
                $newStatus,
                [
                    ProjectStatus::InProgress,
                    ProjectStatus::Cancelled,
                ],
                true
            ),

            ProjectStatus::Completed,
            ProjectStatus::Cancelled => false,
        };
    }

    protected function setPlanned(Project $project): void
    {
        $project->start_date = null;
        $project->end_date = null;
    }

    protected function setInProgress(Project $project): void
    {
        if ($project->start_date === null) {
            $project->start_date = now();
        }

        $project->end_date = null;
    }

    protected function setCompleted(Project $project): void
    {
        if ($project->start_date === null) {
            $project->start_date = now();
        }

        $project->end_date = now();
    }

    protected function setOnHold(Project $project): void
    {
        $project->end_date = null;
    }

    protected function setCancelled(Project $project): void
    {
        $project->end_date = now();
    }
}
