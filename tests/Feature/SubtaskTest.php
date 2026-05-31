<?php

use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| INDEX (nested) — /api/tasks/{task}/subtasks
|--------------------------------------------------------------------------
*/

test('un invitado no puede listar subtareas', function () {
    $task = Task::factory()->for(User::factory())->create();

    $this->getJson("/api/tasks/{$task->id}/subtasks")->assertStatus(401);
});

test('un usuario puede listar subtareas de su tarea', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    Subtask::factory()->count(2)->for($task)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/tasks/{$task->id}/subtasks")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('un usuario no puede listar subtareas de tarea ajena', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $task = Task::factory()->for($victima)->create();
    Subtask::factory()->for($task)->create();

    $this->actingAs($atacante, 'sanctum')
        ->getJson("/api/tasks/{$task->id}/subtasks")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| STORE (nested) — POST /api/tasks/{task}/subtasks
|--------------------------------------------------------------------------
*/

test('un usuario puede crear subtareas en su tarea', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/tasks/{$task->id}/subtasks", ['title' => 'Paso 1'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Paso 1')
        ->assertJsonPath('data.task_id', $task->id);

    $this->assertDatabaseHas('subtasks', [
        'task_id' => $task->id,
        'title' => 'Paso 1',
    ]);
});

test('crear subtarea exige titulo', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/tasks/{$task->id}/subtasks", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('un usuario no puede crear subtareas en tarea ajena', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $task = Task::factory()->for($victima)->create();

    $this->actingAs($atacante, 'sanctum')
        ->postJson("/api/tasks/{$task->id}/subtasks", ['title' => 'Inyectada'])
        ->assertForbidden();

    $this->assertDatabaseMissing('subtasks', ['title' => 'Inyectada']);
});

/*
|--------------------------------------------------------------------------
| SHOW (shallow) — /api/subtasks/{subtask}
|--------------------------------------------------------------------------
*/

test('un usuario puede ver su propia subtarea', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $subtask = Subtask::factory()->for($task)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/subtasks/{$subtask->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $subtask->id);
});

test('un usuario no puede ver subtareas de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $task = Task::factory()->for($victima)->create();
    $subtask = Subtask::factory()->for($task)->create();

    $this->actingAs($atacante, 'sanctum')
        ->getJson("/api/subtasks/{$subtask->id}")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| UPDATE (shallow)
|--------------------------------------------------------------------------
*/

test('un usuario puede actualizar su propia subtarea', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $subtask = Subtask::factory()->for($task)->create(['title' => 'Viejo', 'is_completed' => false]);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/subtasks/{$subtask->id}", ['title' => 'Nuevo', 'is_completed' => true])
        ->assertOk()
        ->assertJsonPath('data.title', 'Nuevo')
        ->assertJsonPath('data.is_completed', true);
});

test('un usuario no puede actualizar subtareas de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $task = Task::factory()->for($victima)->create();
    $subtask = Subtask::factory()->for($task)->create(['title' => 'Privada']);

    $this->actingAs($atacante, 'sanctum')
        ->putJson("/api/subtasks/{$subtask->id}", ['title' => 'Hackeada'])
        ->assertForbidden();

    $this->assertDatabaseHas('subtasks', ['id' => $subtask->id, 'title' => 'Privada']);
});

/*
|--------------------------------------------------------------------------
| DESTROY (shallow)
|--------------------------------------------------------------------------
*/

test('un usuario puede borrar su propia subtarea', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    $subtask = Subtask::factory()->for($task)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/subtasks/{$subtask->id}")
        ->assertNoContent();

    $this->assertModelMissing($subtask);
});

test('un usuario no puede borrar subtareas de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $task = Task::factory()->for($victima)->create();
    $subtask = Subtask::factory()->for($task)->create();

    $this->actingAs($atacante, 'sanctum')
        ->deleteJson("/api/subtasks/{$subtask->id}")
        ->assertForbidden();

    $this->assertModelExists($subtask);
});

/*
|--------------------------------------------------------------------------
| GUEST 401
|--------------------------------------------------------------------------
*/

test('un invitado no puede usar rutas protegidas de subtareas', function (string $method, string $path) {
    $this->json($method, $path)->assertStatus(401);
})->with([
    'nested-store' => ['post',   '/api/tasks/1/subtasks'],
    'show'         => ['get',    '/api/subtasks/1'],
    'update'       => ['put',    '/api/subtasks/1'],
    'destroy'      => ['delete', '/api/subtasks/1'],
]);
