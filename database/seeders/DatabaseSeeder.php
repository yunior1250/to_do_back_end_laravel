<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subtask;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $test = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        $ana = User::factory()->create([
            'name' => 'Ana Perez',
            'email' => 'ana@example.com'
        ]);

        $luis = User::factory()->create([
            'name' => 'Luis Suarez',
            'email' => 'luis@example.com'
        ]);

        foreach ([$test, $ana, $luis] as $user) {

            $category = Category::factory()->for($user)->create();
            $tag = Tag::factory()->for($user)->create();
            Task::factory()
                ->count(3)
                ->for($user)
                ->has(Subtask::factory()->count(rand(0, 3)))
                ->create(['category_id' => $category->id])
                ->each(fn($task) => $task->tags()->attach($tag));
        }
    }
}
