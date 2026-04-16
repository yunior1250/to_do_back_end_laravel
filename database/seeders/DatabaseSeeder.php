<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subtask;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = collect([
            ['name' => 'Test User', 'email' => 'test@example.com'],
            ['name' => 'Ana Pérez', 'email' => 'ana@example.com'],
            ['name' => 'Luis Gómez', 'email' => 'luis@example.com'],
        ])->map(fn ($data) => User::create([
            ...$data,
            'password' => Hash::make('password'),
        ]));

        $categories = collect([
            ['name' => 'Trabajo', 'color' => '#3b82f6'],
            ['name' => 'Personal', 'color' => '#10b981'],
            ['name' => 'Estudios', 'color' => '#f59e0b'],
        ])->map(fn ($data) => Category::create($data));

        $tags = collect([
            ['name' => 'urgente', 'color' => '#ef4444'],
            ['name' => 'importante', 'color' => '#8b5cf6'],
            ['name' => 'rápido', 'color' => '#10b981'],
        ])->map(fn ($data) => Tag::create($data));

        $tasksData = [
            [
                'user_id' => $users[0]->id,
                'category_id' => $categories[0]->id,
                'title' => 'Terminar reporte mensual',
                'description' => 'Preparar el reporte de ventas del mes.',
                'status' => 'in_progress',
                'priority' => 'high',
                'due_date' => now()->addDays(2),
                'is_completed' => false,
            ],
            [
                'user_id' => $users[1]->id,
                'category_id' => $categories[1]->id,
                'title' => 'Comprar víveres',
                'description' => 'Frutas, verduras y pan.',
                'status' => 'pending',
                'priority' => 'medium',
                'due_date' => now()->addDay(),
                'is_completed' => false,
            ],
            [
                'user_id' => $users[2]->id,
                'category_id' => $categories[2]->id,
                'title' => 'Estudiar Laravel',
                'description' => 'Revisar documentación de Eloquent.',
                'status' => 'completed',
                'priority' => 'low',
                'due_date' => now()->subDay(),
                'is_completed' => true,
            ],
        ];

        $tasks = collect($tasksData)->map(fn ($data) => Task::create($data));

        $tasks[0]->tags()->attach([$tags[0]->id, $tags[1]->id]);
        $tasks[1]->tags()->attach([$tags[2]->id]);
        $tasks[2]->tags()->attach([$tags[1]->id, $tags[2]->id]);

        Subtask::create(['task_id' => $tasks[0]->id, 'title' => 'Recopilar datos', 'is_completed' => true]);
        Subtask::create(['task_id' => $tasks[0]->id, 'title' => 'Redactar conclusiones', 'is_completed' => false]);
        Subtask::create(['task_id' => $tasks[1]->id, 'title' => 'Hacer lista', 'is_completed' => false]);
        Subtask::create(['task_id' => $tasks[2]->id, 'title' => 'Leer capítulo 3', 'is_completed' => true]);
    }
}
