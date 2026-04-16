<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'due_date'     => 'datetime',
        ];
    }

    // Una tarea pertenece a un usuario
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Una tarea pertenece a una categoría (opcional)
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Una tarea tiene muchas sub-tareas
    public function subtasks(): HasMany
    {
        return $this->hasMany(Subtask::class);
    }

    // Una tarea tiene muchos tags (muchos a muchos)
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
}
