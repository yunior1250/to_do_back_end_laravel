<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'color',
    ];

    // Un tag puede estar en muchas tareas (muchos a muchos)
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }
}
