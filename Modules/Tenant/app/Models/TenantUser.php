<?php

namespace Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class TenantUser extends Model
{
    use HasRoles, HasPermissions, LogsActivity;

    protected $connection = 'tenant';
    protected $guard_name = 'web';

    protected $table = 'tenant_users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the options for generating the activity log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user that owns the TenantUser
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        )->withDefault();
    }

    /**
     * Get all of the departments for the TenantUser
     */
    public function departments()
    {
        return $this->belongsToMany(
            Department::class,
            'department_users',
            'tenant_user_id',
            'department_id'
        );
    }

    /**
     * Get all of the positions for the TenantUser
     */
    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(
            Position::class,
            'tenant_users_positions',
            'tenant_user_id',
            'position_id'
        );
    }

    /**
     * Get all of the teams for the TenantUser
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            Team::class,
            'tenant_users_teams',
            'tenant_user_id',
            'team_id'
        );
    }
}
