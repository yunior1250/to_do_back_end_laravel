<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [

        'name',
        'color',
    ];

    // Un tag puede estar en muchas tareas (muchos a muchos)
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
