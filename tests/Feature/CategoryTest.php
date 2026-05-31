<?php

use App\Models\Category;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| INDEX
|--------------------------------------------------------------------------
*/

test('un invitado no puede listar categorias', function () {
    $this->getJson('/api/categories')->assertStatus(401);
});

test('un usuario autenticado puede listar sus categorias', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/categories')
        ->assertOk();
});

test('un usuario solo ve sus propias categorias en el index', function () {
    $user = User::factory()->create();
    $otro = User::factory()->create();

    Category::factory()->count(2)->for($user)->create();
    Category::factory()->for($otro)->create(['name' => 'Secreto ajeno']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/categories')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonMissing(['name' => 'Secreto ajeno']);
});

/*
|--------------------------------------------------------------------------
| SHOW
|--------------------------------------------------------------------------
*/

test('un usuario puede ver sus propias categorias', function () {
    $user = User::factory()->create();
    $categoria = Category::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/categories/{$categoria->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $categoria->id)
        ->assertJsonPath('data.name', $categoria->name);
});

test('un usuario no puede ver categorias de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $categoria = Category::factory()->for($victima)->create();

    $this->actingAs($atacante, 'sanctum')
        ->getJson("/api/categories/{$categoria->id}")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| STORE
|--------------------------------------------------------------------------
*/

test('un usuario autenticado puede crear una categoria', function () {
    $user = User::factory()->create();

    $payload = ['name' => 'Trabajo', 'color' => '#ff0000'];

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/categories', $payload)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Trabajo')
        ->assertJsonPath('data.color', '#ff0000');

    $this->assertDatabaseHas('categories', [
        'name' => 'Trabajo',
        'color' => '#ff0000',
        'user_id' => $user->id,
    ]);
});

test('crear categoria valida campos requeridos', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/categories', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'color']);
});

test('crear categoria rechaza color con formato invalido', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/categories', ['name' => 'Personal', 'color' => 'rojo'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['color']);
});

test('el mismo usuario no puede repetir el nombre de categoria', function () {
    $user = User::factory()->create();
    Category::factory()->for($user)->create(['name' => 'Trabajo']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/categories', ['name' => 'Trabajo', 'color' => '#000000'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('dos usuarios distintos pueden tener categorias con el mismo nombre', function () {
    $otro = User::factory()->create();
    Category::factory()->for($otro)->create(['name' => 'Trabajo']);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/categories', ['name' => 'Trabajo', 'color' => '#123456'])
        ->assertCreated();

    $this->assertDatabaseCount('categories', 2);
});

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

test('un usuario puede actualizar su propia categoria', function () {
    $user = User::factory()->create();
    $categoria = Category::factory()->for($user)->create(['name' => 'Viejo']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/categories/{$categoria->id}", ['name' => 'Nuevo'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nuevo');

    $this->assertDatabaseHas('categories', [
        'id' => $categoria->id,
        'name' => 'Nuevo',
    ]);
});

test('actualizar puede mantener su propio nombre sin chocar con la regla unique', function () {
    $user = User::factory()->create();
    $categoria = Category::factory()->for($user)->create(['name' => 'Trabajo']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/categories/{$categoria->id}", [
            'name' => 'Trabajo',
            'color' => '#abcdef',
        ])
        ->assertOk();
});

test('un usuario no puede actualizar categorias de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $categoria = Category::factory()->for($victima)->create(['name' => 'Privada']);

    $this->actingAs($atacante, 'sanctum')
        ->putJson("/api/categories/{$categoria->id}", ['name' => 'Hackeada'])
        ->assertForbidden();

    $this->assertDatabaseHas('categories', [
        'id' => $categoria->id,
        'name' => 'Privada',
    ]);
});

test('actualizar valida formato de color', function () {
    $user = User::factory()->create();
    $categoria = Category::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/categories/{$categoria->id}", ['color' => 'verde'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['color']);
});

/*
|--------------------------------------------------------------------------
| DESTROY
|--------------------------------------------------------------------------
*/

test('un usuario puede borrar su propia categoria', function () {
    $user = User::factory()->create();
    $categoria = Category::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/categories/{$categoria->id}")
        ->assertNoContent();

    $this->assertModelMissing($categoria);
});

test('un usuario no puede borrar categorias de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $categoria = Category::factory()->for($victima)->create();

    $this->actingAs($atacante, 'sanctum')
        ->deleteJson("/api/categories/{$categoria->id}")
        ->assertForbidden();

    $this->assertModelExists($categoria);
});

/*
|--------------------------------------------------------------------------
| GUEST 401 — POST / PUT / DELETE
|--------------------------------------------------------------------------
*/

test('un invitado no puede usar rutas protegidas de categorias', function (string $method, string $path) {
    $this->json($method, $path)->assertStatus(401);
})->with([
    'store'   => ['post',   '/api/categories'],
    'update'  => ['put',    '/api/categories/1'],
    'destroy' => ['delete', '/api/categories/1'],
]);