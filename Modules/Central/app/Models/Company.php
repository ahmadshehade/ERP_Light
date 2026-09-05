<?php

namespace Modules\Central\Models;

use App\Enums\NameOfRoles;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

// use Modules\Central\Database\Factories\CompanyFactory;

class Company extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, HasTranslations, InteractsWithMedia, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'subdomain', 'max_users', 'is_active', 'database'];
    protected $guarded = ['owner_id'];

    protected $casts = [
        'is_active' => 'boolean',
        'name' => 'array',
    ];

    protected $translatable = [
        'name',
    ];


    /**
     *  Get the user that owns the company.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all of the subscriptions for the Company.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'company_id');
    }

    /**
     * Summary of scopeUserCompanies
     * @param Builder $query
     * @param User $user
     * @return Builder
     */
    public function scopeUserCompanies(Builder $query, User $user): Builder
    {
        if ($user->hasRole(NameOfRoles::SuperAdmin->value)) {
            return $query;
        }
        return $query->where('owner_id', $user->id);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('company')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/jpg',
                'image/gif',
                'image/webp',
            ]);
    }

    public function registerMediaConversions(Media $media = null): void
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
     * Get the tenant that owns the company.
     */
    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class, 'company_id');
    }

    /**
     * Get the options for generating the activity log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }




    // protected static function newFactory(): CompanyFactory
    // {
    //     // return CompanyFactory::new();
    // }
}
