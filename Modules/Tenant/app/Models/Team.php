<?php

namespace Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Enum\TenantRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Team extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasTranslations;

    protected $table = 'teams';

    protected $connection = 'tenant';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /**
     * Translatable attributes.
     */
    protected $translatable = [
        'name',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all teams available for the TenantUser.
     */
    public function scopeActive(Builder $builder, User $user): Builder
    {
        $tenantUser = $user->tenantUsers()
            ->where('user_id', $user->id)
            ->first();

        if ($tenantUser->hasRole(TenantRoles::Owner->value)) {
            return $builder;
        }

        return $builder->where('is_active', true);
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('team')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/jpg',
                'image/gif',
                'image/webp',
            ]);
    }

    /**
     * Register media conversions.
     */
    public function registerAllMediaConversions(?Media $media = null): void
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
     * Get all of the users for the Team
     */
    public function tenantUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantUser::class,
            'tenant_users_teams',
            'team_id',
            'tenant_user_id'
        );
    }

    /**
     * Get all of the projects for the Team
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_teams', 'team_id', 'project_id');
    }

    /**
     * Get all of the tasks for the Team
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'team_id');
    }
}
