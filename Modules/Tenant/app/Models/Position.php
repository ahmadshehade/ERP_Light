<?php

namespace Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Enum\TenantRoles;
use Spatie\Translatable\HasTranslations;
use Illuminate\Support\Str;
// use Modules\Tenant\Database\Factories\PositionFactory;

class Position extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The database connection used by the model.
     */
    protected $connection = 'tenant';
    /**
     * The table associated with the model.
     */
    protected $table = 'positions';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',

    ];


    /**
     * Boot the model
     */
    protected static function booted()
    {
        static::creating(function (Position $positoin) {
            $positoin->uuid = (string)Str::ulid();
        });
    }



    /**
     * The attributes that are translatable.
     */
    protected $translatable = ['name', 'description'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Get all of the positions for the TenantUser
     * @parma User $user
     * @return Builder
     */
    public function scopeAvaliable(Builder $builder, User $user): Builder
    {
        $tenantUser = TenantUser::where('user_id', $user->id)->first();
        if ($tenantUser->hasRole(TenantRoles::Owner->value)) {
            return $builder;
        }
        return $builder->where('is_active', true);
    }

    /**
     * Get all of the tenantUsers for the Position
     * @return BelongsToMany
     */
    public function tenantUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantUser::class,
            'tenant_users_positions',
            'position_id',
            'tenant_user_id'
        );
    }



    // protected static function newFactory(): PositionFactory
    // {
    //     // return PositionFactory::new();
    // }
}
