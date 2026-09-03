<?php

namespace Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Enum\ProjectPriority;
use Modules\Tenant\Enum\ProjectStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;
use Illuminate\Support\Str;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Enum\TenantRoles;

// use Modules\Tenant\Database\Factories\ProjectFactory;

class Project extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, HasTranslations;

    protected $table = 'projects';

    protected $connection = 'tenant';

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => ProjectStatus::class,
        'priority' => ProjectPriority::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];
    protected $translatable = [
        'name',
        'description',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'status',
        'priority',
        'start_date',
        'end_date'
    ];


    /**
     * Boot the model
     */
    protected static function booted()
    {
        return static::creating(function (Project $project) {
            $project->uuid = (string)Str::ulid();
        });
    }


    /**
     * Scope a query to only include active users.
     */
    public function scopeActive(Builder $query, User $user): Builder
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->firstOrFail();
        if (! $tenantUser->hasPermissionTo(
            TenantPermission::TenantViewAnyProjects->value
        )) {
            return $query->whereRaw('1 = 0');
        }
        if ($tenantUser->hasRole(TenantRoles::Owner->value)) {
            return $query;
        }
        if ($tenantUser->hasRole(TenantRoles::Manager->value)) {
            return $query->where('is_active', true);
        }
        if ($tenantUser->hasRole(TenantRoles::Employee->value)) {
            return $query
                ->where('is_active', true)
                ->whereHas('teams.tenantUsers', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
        }


        if ($tenantUser->hasRole(TenantRoles::Guest->value)) {
            return $query
                ->where('is_active', true)
                ->where('status', ProjectStatus::Completed);
        }

        return $query->whereRaw('1 = 0');
    }

    // protected static function newFactory(): ProjectFactory
    // {
    //     // return ProjectFactory::new();
    // }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile')
            ->acceptsFile(function ($file) {
                return in_array(
                    $file->mimeType,
                    [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'image/gif',

                        'application/pdf',
                        'text/plain',

                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',

                        'text/csv',

                        'application/zip',
                        'application/x-rar-compressed',
                    ],
                    true
                );
            });
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(100)
            ->height(100)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(300)
            ->height(300)
            ->sharpen(10);
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $attributes = [
        'status' => ProjectStatus::Planned->value,
        'priority' => ProjectPriority::High->value
    ];

    /**
     * Get the teams for the project.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'project_teams', 'project_id', 'team_id');
    }

    /**
     * Get the tasks for the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }
}
