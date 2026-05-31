<?php

use App\Models\Category;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| INDEX
|--------------------------------------------------------------------------
*/

test('un invitado no puede listar tareas', function () {
    $this->getJson('/api/tasks')->assertStatus(401);
});

test('un usuario autenticado puede listar sus tareas', function () {
    $user = User::factory()->create();
    Task::factory()->count(3)->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('un usuario solo ve sus propias tareas', function () {
    $user = User::factory()->create();
    $otro = User::factory()->create();

    Task::factory()->count(2)->for($user)->create();
    Task::factory()->for($otro)->create(['title' => 'Tarea ajena']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissing(['title' => 'Tarea ajena']);
});

test('index incluye relaciones category, tags y subtasks', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $tag = Tag::factory()->for($user)->create();
    $task = Task::factory()->for($user)->create(['category_id' => $category->id]);
    $task->tags()->attach($tag);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonPath('data.0.category.id', $category->id)
        ->assertJsonPath('data.0.tags.0.id', $tag->id)
        ->assertJsonPath('data.0.subtasks', []);
});

/*
|--------------------------------------------------------------------------
| SHOW
|--------------------------------------------------------------------------
*/

test('un usuario puede ver su propia tarea', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $task->id);
});

test('un usuario no puede ver tareas de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $task = Task::factory()->for($victima)->create();

    $this->actingAs($atacante, 'sanctum')
        ->getJson("/api/tasks/{$task->id}")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| STORE
|--------------------------------------------------------------------------
*/

test('un usuario autenticado puede crear una tarea simple', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', ['title' => 'Comprar pan'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Comprar pan');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Comprar pan',
        'user_id' => $user->id,
    ]);
});

test('un usuario puede crear una tarea con todos los campos', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create();
    $tag1 = Tag::factory()->for($user)->create();
    $tag2 = Tag::factory()->for($user)->create();

    $payload = [
        'category_id' => $category->id,
        'title' => 'Hacer informe',
        'description' => 'Informe trimestral',
        'status' => 'in_progress',
        'priority' => 'high',
        'due_date' => '2027-01-15 10:00:00',
        'is_completed' => false,
        'tag_ids' => [$tag1->id, $tag2->id],
    ];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', $payload)
        ->assertCreated()
        ->assertJsonPath('data.title', 'Hacer informe')
        ->assertJsonPath('data.status', 'in_progress')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonCount(2, 'data.tags');

    $this->assertDatabaseCount('tag_task', 2);
});

test('crear tarea exige titulo', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('crear tarea valida enums de status y priority', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', [
            'title' => 'x',
            'status' => 'invalido',
            'priority' => 'super-alta',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status', 'priority']);
});

test('crear tarea rechaza due_date no parseable', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', [
            'title' => 'x',
            'due_date' => 'mañana',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['due_date']);
});

test('no puede asignar una categoria de otro usuario', function () {
    $user = User::factory()->create();
    $otro = User::factory()->create();
    $catAjena = Category::factory()->for($otro)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', [
            'title' => 'x',
            'category_id' => $catAjena->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['category_id']);
});

test('no puede adjuntar tags de otro usuario', function () {
    $user = User::factory()->create();
    $otro = User::factory()->create();
    $tagPropio = Tag::factory()->for($user)->create();
    $tagAjeno = Tag::factory()->for($otro)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tasks', [
            'title' => 'x',
            'tag_ids' => [$tagPropio->id, $tagAjeno->id],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['tag_ids.1']);
});

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

test('un usuario puede actualizar su propia tarea', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'Viejo']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/tasks/{$task->id}", ['title' => 'Nuevo'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Nuevo');
});

test('actualizar tarea puede sincronizar tags', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $tagInicial = Tag::factory()->for($user)->create();
    $task->tags()->attach($tagInicial);

    $nuevoTag = Tag::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/tasks/{$task->id}", ['tag_ids' => [$nuevoTag->id]])
        ->assertOk()
        ->assertJsonCount(1, 'data.tags')
        ->assertJsonPath('data.tags.0.id', $nuevoTag->id);

    expect($task->fresh()->tags->pluck('id')->all())->toBe([$nuevoTag->id]);
});

test('actualizar con tag_ids vacio desasocia todos los tags', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $task->tags()->attach(Tag::factory()->for($user)->create());

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/tasks/{$task->id}", ['tag_ids' => []])
        ->assertOk()
        ->assertJsonCount(0, 'data.tags');

    expect($task->fresh()->tags)->toHaveCount(0);
});

test('un usuario no puede actualizar tareas de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $task = Task::factory()->for($victima)->create(['title' => 'Privada']);

    $this->actingAs($atacante, 'sanctum')
        ->putJson("/api/tasks/{$task->id}", ['title' => 'Hackeada'])
        ->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Privada']);
});

/*
|--------------------------------------------------------------------------
| DESTROY
|--------------------------------------------------------------------------
*/

test('un usuario puede borrar su propia tarea', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/tasks/{$task->id}")
        ->assertNoContent();

    $this->assertModelMissing($task);
});

test('un usuario no puede borrar tareas de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $task = Task::factory()->for($victima)->create();

    $this->actingAs($atacante, 'sanctum')
        ->deleteJson("/api/tasks/{$task->id}")
        ->assertForbidden();

    $this->assertModelExists($task);
});

/*
|--------------------------------------------------------------------------
| GUEST 401
|--------------------------------------------------------------------------
*/

test('un invitado no puede usar rutas protegidas de tareas', function (string $method, string $path) {
    $this->json($method, $path)->assertStatus(401);
})->with([
    'store'   => ['post',   '/api/tasks'],
    'update'  => ['put',    '/api/tasks/1'],
    'destroy' => ['delete', '/api/tasks/1'],
]);
