<?php

namespace Modules\Tenant\Services\Project;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Notifications\Projects\CanceledProjectNotification;
use Modules\Tenant\Notifications\Projects\CompletedProjectNotification;
use Modules\Tenant\Notifications\Projects\DeleteProjectNotification;
use Modules\Tenant\Notifications\Projects\MakeProjectNotification;
use Modules\Tenant\Notifications\Projects\OnHoldProjectNotification;
use Modules\Tenant\Notifications\Projects\RestoreProjectNotification;
use Modules\Tenant\Notifications\Projects\ResumedProjectNotification;
use Modules\Tenant\Notifications\Projects\StartProjectNotification;
use Modules\Tenant\Notifications\Projects\UpdateProjectNotification;

class ProjectNotificationService
{
    /**
     * Prepare project notification data.
     */
    private function prepareData(Project $project): array
    {
        $tenant = tenant();

        $company = $tenant->company;
        $teams = $project->teams->map(fn($team) => [
            'id' => $team->id,
            'team_name_en' => $team->getTranslation('name', 'en'),
            'team_name_ar' => $team->getTranslation('name', 'ar'),
        ])->values()->all();

        return [
            'project_name_ar' => $project->getTranslation('name', 'ar'),
            'project_name_en' => $project->getTranslation('name', 'en'),

            'project_description_ar' => $project->getTranslation(
                'description',
                'ar'
            ),

            'project_description_en' => $project->getTranslation(
                'description',
                'en'
            ),

            'status' => $project->status,
            'priority' => $project->priority,

            'start_date' => $project->start_date,
            'end_date' => $project->end_date,

            'is_active' => (bool) $project->is_active,

            'company_name_ar' => $company->getTranslation('name', 'ar'),
            'company_name_en' => $company->getTranslation('name', 'en'),
            'teams' => $teams,

        ];
    }

    /**
     * Get project notification receivers.
     *
     * Owner + all Managers.
     */
    private function getReceivers(): Collection
    {
        $owner = TenantUser::role(
            TenantRoles::Owner->value
        )->with('user')->first()?->user;
        $managers = TenantUser::role(
            TenantRoles::Manager->value
        )->with('user')->get()->pluck('user');

        logger()->info('managers:' . $managers);

        return collect([$owner])
            ->merge($managers)
            ->filter()
            ->unique('id')->values();
    }

    /**
     * Notify owner and managers about a newly created project.
     */
    public function makeNewProjectNotify(Project $project): void
    {
        $receivers = $this->getReceivers();

        if ($receivers->isEmpty()) {
            return;
        }

        $data = $this->prepareData($project);

        Notification::send(
            $receivers,
            new MakeProjectNotification(
                name_en: $data['project_name_en'],
                name_ar: $data['project_name_ar'],
                description_en: $data['project_description_en'],
                description_ar: $data['project_description_ar'],
                status: $data['status'],
                priority: $data['priority'],
                company_name_en: $data['company_name_en'],
                company_name_ar: $data['company_name_ar'],
                is_active: $data['is_active'],
                teams: $data['teams'],
            )
        );
    }

    /**
     * Notify owner and managers about an updated project.
     */
    public function updateProjectNotify(Project $project): void
    {
        $receivers = $this->getReceivers();

        if ($receivers->isEmpty()) {
            return;
        }

        $data = $this->prepareData($project);

        Notification::send(
            $receivers,
            new UpdateProjectNotification(
                name_en: $data['project_name_en'],
                name_ar: $data['project_name_ar'],
                description_en: $data['project_description_en'],
                description_ar: $data['project_description_ar'],
                status: $data['status'],
                priority: $data['priority'],
                company_name_en: $data['company_name_en'],
                company_name_ar: $data['company_name_ar'],
                is_active: $data['is_active'],
                teams: $data['teams'],
            )
        );
    }

    /**
     * Notify owner and managers about a deleted project.
     *
     * This method expects prepared project data.
     */
    public function deleteProjectNotify(array $data): void
    {
        $receivers = $this->getReceivers();

        if ($receivers->isEmpty()) {
            return;
        }

        Notification::send(
            $receivers,
            new DeleteProjectNotification(
                name_en: $data['project_name_en'],
                name_ar: $data['project_name_ar'],
                description_en: $data['project_description_en'],
                description_ar: $data['project_description_ar'],
                status: $data['status'],
                priority: $data['priority'],
                company_name_en: $data['company_name_en'],
                company_name_ar: $data['company_name_ar'],
                is_active: (bool) $data['is_active'],
            )
        );
    }

    /**
     * Notify owner and managers about a restored project.
     */
    public function restoreProjectNotify(Project $project): void
    {
        $receivers = $this->getReceivers();

        if ($receivers->isEmpty()) {
            return;
        }

        $data = $this->prepareData($project);

        Notification::send(
            $receivers,
            new RestoreProjectNotification(
                name_en: $data['project_name_en'],
                name_ar: $data['project_name_ar'],
                description_en: $data['project_description_en'],
                description_ar: $data['project_description_ar'],
                status: $data['status'],
                priority: $data['priority'],
                company_name_en: $data['company_name_en'],
                company_name_ar: $data['company_name_ar'],
                is_active: $data['is_active'],
            )
        );
    }

    public function startProjectNotify(Project $project): void
    {
        $receivers = $this->getReceivers();
        $data = $this->prepareData($project);
        Notification::send($receivers, new StartProjectNotification(
            name_en: $data['project_name_en'],
            name_ar: $data['project_name_ar'],
            description_en: $data['project_description_en'],
            description_ar: $data['project_description_ar'],
            status: $data['status'],
            priority: $data['priority'],
            company_name_en: $data['company_name_en'],
            company_name_ar: $data['company_name_ar'],
            is_active: $data['is_active'],
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            teams: $data['teams'],
        ));
    }

    /**
     * Summary of OnHoldProjectNotify
     * @param Project $project
     * @return void
     */
    public function onHoldProjectNotify(Project $project): void
    {
        $receivers = $this->getReceivers();
        $data = $this->prepareData($project);
        Notification::send($receivers, new OnHoldProjectNotification(
            name_en: $data['project_name_en'],
            name_ar: $data['project_name_ar'],
            description_en: $data['project_description_en'],
            description_ar: $data['project_description_ar'],
            status: $data['status'],
            priority: $data['priority'],
            company_name_en: $data['company_name_en'],
            company_name_ar: $data['company_name_ar'],
            is_active: $data['is_active'],
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            teams: $data['teams'],
        ));
    }


    /**
     * Summary of resumeProjectNotify
     * @param Project $project
     * @return void
     */
    public function resumeProjectNotify(Project $project): void
    {
        $receivers = $this->getReceivers();
        $data = $this->prepareData($project);
        Notification::send($receivers, new ResumedProjectNotification(
            name_en: $data['project_name_en'],
            name_ar: $data['project_name_ar'],
            description_en: $data['project_description_en'],
            description_ar: $data['project_description_ar'],
            status: $data['status'],
            priority: $data['priority'],
            company_name_en: $data['company_name_en'],
            company_name_ar: $data['company_name_ar'],
            is_active: $data['is_active'],
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            teams: $data['teams'],
        ));
    }


    /**
     * Summary of cancelProjectNotify
     * @param Project $project
     * @return void
     */
    public function cancelProjectNotify(Project $project): void
    {
        $receivers = $this->getReceivers();
        $data = $this->prepareData($project);
        Notification::send($receivers, new CanceledProjectNotification(
            name_en: $data['project_name_en'],
            name_ar: $data['project_name_ar'],
            description_en: $data['project_description_en'],
            description_ar: $data['project_description_ar'],
            status: $data['status'],
            priority: $data['priority'],
            company_name_en: $data['company_name_en'],
            company_name_ar: $data['company_name_ar'],
            is_active: $data['is_active'],
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            teams: $data['teams'],
        ));
    }

    /**
     * Summary of completeNotify
     * @param Project $project
     * @return void
     */
    public function completeProjectNotify(Project $project): void
    {
        $receivers = $this->getReceivers();
        $data = $this->prepareData($project);
        Notification::send($receivers, new CompletedProjectNotification(
            name_en: $data['project_name_en'],
            name_ar: $data['project_name_ar'],
            description_en: $data['project_description_en'],
            description_ar: $data['project_description_ar'],
            status: $data['status'],
            priority: $data['priority'],
            company_name_en: $data['company_name_en'],
            company_name_ar: $data['company_name_ar'],
            is_active: $data['is_active'],
            start_date: $data['start_date'],
            end_date: $data['end_date'],
            teams: $data['teams'],
        ));
    }
}
