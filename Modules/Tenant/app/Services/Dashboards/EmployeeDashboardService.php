<?php

namespace Modules\Tenant\Services\Dashboards;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Models\TenantUser;

class EmployeeDashboardService
{
    /**
     * Get the complete Employee Dashboard.
     */
    public function getDashboard(): array
    {
        $tenantUser = $this->getEmployee();

        return [
            'role' => TenantRoles::Employee->value,

            'departments' => $this->getDepartmentsStatistics($tenantUser),

            'positions' => $this->getPositionsStatistics($tenantUser),

            'teams' => $this->getTeamsStatistics($tenantUser),

            'projects' => $this->getProjectsStatistics($tenantUser),

            'tasks' => $this->getTasksStatistics($tenantUser),
        ];
    }

    /**
     * Get authenticated Employee.
     */
    protected function getEmployee(): TenantUser
    {
        $user = Auth::user();

        $tenantUser = TenantUser::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless(
            $tenantUser->hasRole(TenantRoles::Employee->value),
            403,
            'Only the employee can access this dashboard.'
        );

        return $tenantUser;
    }

    /**
     * Departments assigned to the employee.
     */
    protected function getDepartmentsStatistics(
        TenantUser $tenantUser
    ): array {
        $query = $tenantUser->departments()
            ->where('departments.is_active', true);

        return [
            'total' => (clone $query)->count(),
        ];
    }

    /**
     * Positions assigned to the employee.
     */
    protected function getPositionsStatistics(
        TenantUser $tenantUser
    ): array {
        $query = $tenantUser->positions()
            ->where('positions.is_active', true);

        return [
            'total' => (clone $query)->count(),
        ];
    }

    /**
     * Teams assigned to the employee.
     */
    protected function getTeamsStatistics(
        TenantUser $tenantUser
    ): array {
        $query = $tenantUser->teams()
            ->where('teams.is_active', true);

        return [
            'total' => (clone $query)->count(),

            'projects' => (clone $query)
                ->withCount('projects')
                ->get()
                ->sum('projects_count'),

            'tasks' => (clone $query)
                ->withCount('tasks')
                ->get()
                ->sum('tasks_count'),
        ];
    }

    /**
     * Projects accessible by the employee.
     */
    protected function getProjectsStatistics(
        TenantUser $tenantUser
    ): array {
        $query = Project::query()
            ->where('is_active', true)
            ->whereHas('teams.tenantUsers', function ($query) use ($tenantUser) {
                $query->where(
                    'tenant_users.id',
                    $tenantUser->id
                );
            });

        $today = Carbon::today();

        return [
            'total' => (clone $query)->count(),

            'planned' => (clone $query)
                ->where('status', ProjectStatus::Planned->value)
                ->count(),

            'in_progress' => (clone $query)
                ->where('status', ProjectStatus::InProgress->value)
                ->count(),

            'on_hold' => (clone $query)
                ->where('status', ProjectStatus::OnHold->value)
                ->count(),

            'completed' => (clone $query)
                ->where('status', ProjectStatus::Completed->value)
                ->count(),

            'cancelled' => (clone $query)
                ->where('status', ProjectStatus::Cancelled->value)
                ->count(),

            'overdue' => (clone $query)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', $today)
                ->whereNotIn('status', [
                    ProjectStatus::Completed->value,
                    ProjectStatus::Cancelled->value,
                ])
                ->count(),
        ];
    }

    /**
     * Tasks accessible by the employee.
     */
    protected function getTasksStatistics(
        TenantUser $tenantUser
    ): array {
        $query = Task::query()
            ->where('is_active', true)
            ->whereHas('team.tenantUsers', function ($query) use ($tenantUser) {
                $query->where(
                    'tenant_users.id',
                    $tenantUser->id
                );
            });

        $today = Carbon::today();

        return [
            'total' => (clone $query)->count(),

            'open' => (clone $query)
                ->where('status', TaskStatus::OPEN->value)
                ->count(),

            'in_progress' => (clone $query)
                ->where('status', TaskStatus::IN_PROGRESS->value)
                ->count(),

            'completed' => (clone $query)
                ->where('status', TaskStatus::COMPLETED->value)
                ->count(),

            'on_hold' => (clone $query)
                ->where('status', TaskStatus::OnHold->value)
                ->count(),

            'cancelled' => (clone $query)
                ->where('status', TaskStatus::CANCELLED->value)
                ->count(),

            'overdue' => (clone $query)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->whereNotIn('status', [
                    TaskStatus::COMPLETED->value,
                    TaskStatus::CANCELLED->value,
                ])
                ->count(),

            'due_today' => (clone $query)
                ->whereNotNull('due_date')
                ->whereDate('due_date', $today)
                ->whereNotIn('status', [
                    TaskStatus::COMPLETED->value,
                    TaskStatus::CANCELLED->value,
                ])
                ->count(),
        ];
    }
}
