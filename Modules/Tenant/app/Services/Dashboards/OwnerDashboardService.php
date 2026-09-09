<?php

namespace Modules\Tenant\Services\Dashboards;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Tenant\Enum\ProjectStatus;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Activity;
use Modules\Tenant\Models\Department;
use Modules\Tenant\Models\Position;
use Modules\Tenant\Models\Project;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Models\Team;
use Modules\Tenant\Models\TenantUser;

class OwnerDashboardService
{
    /**
     * Get the complete Owner Dashboard.
     */
    public function getDashboard(): array
    {
        $owner = $this->getOwner();

        return [
            'role' => TenantRoles::Owner->value,

            'company' => $this->getCompanyStatistics(),

            'users' => $this->getUsersStatistics(),

            'departments' => $this->getDepartmentsStatistics(),

            'positions' => $this->getPositionsStatistics(),

            'teams' => $this->getTeamsStatistics(),

            'projects' => $this->getProjectsStatistics(),

            'tasks' => $this->getTasksStatistics(),

            'activity' => $this->getActivityStatistics(),
        ];
    }

    /**
     * Get the authenticated Owner.
     */
    protected function getOwner(): TenantUser
    {
        $user = Auth::user();

        $tenantUser = TenantUser::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        abort_unless(
            $tenantUser->hasRole(TenantRoles::Owner->value),
            403,
            'Only the company owner can access this dashboard.'
        );

        return $tenantUser;
    }

    /**
     * Company statistics.
     *
     * The current Tenant represents one company,
     * therefore company-level information is resolved
     * from the current tenant/company context.
     */
    protected function getCompanyStatistics(): array
    {
        $tenant = tenant();

        $company = $tenant?->company;

        if (! $company) {
            return [
                'id' => null,
                'name' => null,
                'subdomain' => null,
                'max_users' => null,
                'is_active' => null,
                'current_users' => TenantUser::query()->count(),
            ];
        }

        return [
            'id' => $company->id,
            'name' => $company->name,
            'subdomain' => $company->subdomain,
            'max_users' => $company->max_users,
            'is_active' => (bool) $company->is_active,

            'current_users' => TenantUser::query()->count(),
        ];
    }

    /**
     * Tenant users statistics.
     */
    protected function getUsersStatistics(): array
    {
        $last30Days = Carbon::now()->subDays(30);

        $query = TenantUser::query();

        return [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'inactive' => (clone $query)
                ->where('is_active', false)
                ->count(),

            'owners' => (clone $query)
                ->role(TenantRoles::Owner->value)
                ->count(),

            'managers' => (clone $query)
                ->role(TenantRoles::Manager->value)
                ->count(),

            'employees' => (clone $query)
                ->role(TenantRoles::Employee->value)
                ->count(),

            'guests' => (clone $query)
                ->role(TenantRoles::Guest->value)
                ->count(),

            'new_last_30_days' => (clone $query)
                ->where('created_at', '>=', $last30Days)
                ->count(),
        ];
    }

    /**
     * Departments statistics.
     */
    protected function getDepartmentsStatistics(): array
    {
        $query = Department::query();

        return [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'inactive' => (clone $query)
                ->where('is_active', false)
                ->count(),

            'deleted' => (clone $query)
                ->onlyTrashed()
                ->count(),

            'new_last_30_days' => (clone $query)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),

            'members' => (clone $query)
                ->withCount('tenantUsers')
                ->get()
                ->sum('tenant_users_count'),
        ];
    }

    /**
     * Positions statistics.
     */
    protected function getPositionsStatistics(): array
    {
        $query = Position::query();

        return [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'inactive' => (clone $query)
                ->where('is_active', false)
                ->count(),

            'deleted' => (clone $query)
                ->onlyTrashed()
                ->count(),

            'new_last_30_days' => (clone $query)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),

            'assigned_users' => (clone $query)
                ->withCount('tenantUsers')
                ->get()
                ->sum('tenant_users_count'),
        ];
    }

    /**
     * Teams statistics.
     */
    protected function getTeamsStatistics(): array
    {
        $query = Team::query();

        return [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'inactive' => (clone $query)
                ->where('is_active', false)
                ->count(),

            'new_last_30_days' => (clone $query)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count(),

            'members' => (clone $query)
                ->withCount('tenantUsers')
                ->get()
                ->sum('tenant_users_count'),

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
     * Projects statistics.
     */
    protected function getProjectsStatistics(): array
    {
        $query = Project::query();

        $today = Carbon::today();
        $last30Days = Carbon::now()->subDays(30);

        return [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'inactive' => (clone $query)
                ->where('is_active', false)
                ->count(),

            'deleted' => (clone $query)
                ->onlyTrashed()
                ->count(),

            'new_last_30_days' => (clone $query)
                ->where('created_at', '>=', $last30Days)
                ->count(),

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

            'without_team' => (clone $query)
                ->doesntHave('teams')
                ->count(),

            'without_tasks' => (clone $query)
                ->doesntHave('tasks')
                ->count(),
        ];
    }

    /**
     * Tasks statistics.
     */
    protected function getTasksStatistics(): array
    {
        $query = Task::query();

        $today = Carbon::today();
        $last30Days = Carbon::now()->subDays(30);

        return [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->where('is_active', true)
                ->count(),

            'inactive' => (clone $query)
                ->where('is_active', false)
                ->count(),

            'deleted' => (clone $query)
                ->onlyTrashed()
                ->count(),

            'new_last_30_days' => (clone $query)
                ->where('created_at', '>=', $last30Days)
                ->count(),

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

            'without_project' => (clone $query)
                ->whereNull('project_id')
                ->count(),

            'without_team' => (clone $query)
                ->whereNull('team_id')
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

    /**
     * Activity log statistics.
     */
    protected function getActivityStatistics(): array
    {
        $last30Days = Carbon::now()->subDays(30);

        $query = Activity::query();

        return [
            'total' => (clone $query)->count(),

            'last_30_days' => (clone $query)
                ->where('created_at', '>=', $last30Days)
                ->count(),

            'today' => (clone $query)
                ->whereDate('created_at', Carbon::today())
                ->count(),

            'latest' => (clone $query)
                ->latest()
                ->limit(10)
                ->get([
                    'id',
                    'log_name',
                    'description',
                    'subject_type',
                    'subject_id',
                    'causer_type',
                    'causer_id',
                    'created_at',
                ]),
        ];
    }
}
