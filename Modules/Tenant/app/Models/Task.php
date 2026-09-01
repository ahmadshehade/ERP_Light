<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Enum\TaskPriority;
use Modules\Tenant\Enum\TaskStatus;
use Spatie\Translatable\HasTranslations;

// use Modules\Tenant\Database\Factories\TaskFactory;

class Task extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

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

    // protected static function newFactory(): TaskFactory
    // {
    //     // return TaskFactory::new();
    // }
}
