<?php

namespace Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Enum\TaskPriority;
use Modules\Tenant\Enum\TaskStatus;
use Modules\Tenant\Enum\TenantPermission;
use Modules\Tenant\Enum\TenantRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

// use Modules\Tenant\Database\Factories\TaskFactory;

class Task extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, HasTranslations, InteractsWithMedia;

    protected $table = 'tasks';

    protected $connection = 'tenant';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'project_id',
        'team_id',
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'is_active'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class
    ];

    /**
     * The attributes that are translatable.
     */
    protected $translatable = ['title', 'description'];

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the team that owns the task.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the default values for the task.
     */
    protected $attributes = [
        'status' => TaskStatus::OPEN->value,
        'is_active' => true,
    ];


    /**
     * Scope a query to only include active users.
     */
    public function scopeActive(Builder $query, User $user): Builder
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->firstOrFail();
        if (! $tenantUser->hasPermissionTo(
            TenantPermission::TenantViewAnyTask->value
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
                ->whereHas('team.tenantUsers', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('task')
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
    public function registerMediaConversions(\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(368)
            ->height(232)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600)
            ->sharpen(10);
    }

    // protected static function newFactory(): TaskFactory
    // {
    //     // return TaskFactory::new();
    // }
}
