<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Enum\TenantRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

// use Modules\Tenant\Database\Factories\DepartmentFactory;

class Department extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia, SoftDeletes;

    protected $table = 'departments';

    protected $connection = 'tenant';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    protected $translatable = ['name', 'description'];

    /**
     * Register all media collections for this model.
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('department')
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
     * Register all media conversions for this model.
     * @param Media|null $media
     * @return void
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
     * Get all of the positions for the TenantUser
     */
    public function scopeActive(Builder $query, TenantUser $tenantUser): Builder
    {
        if ($tenantUser->hasRole(TenantRoles::Owner->value)) {
            return $query;
        }
        return $query->where('is_active', true);
    }

    /**
     * Get all of the users for the Department
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tenantUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantUser::class,
            'department_users',
            'department_id',
            'tenant_user_id'
        );
    }

    // protected static function newFactory(): DepartmentFactory
    // {
    //     // return DepartmentFactory::new();
    // }
}
