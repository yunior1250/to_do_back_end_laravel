<?php

use App\Models\Tag;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| INDEX
|--------------------------------------------------------------------------
*/

test('un invitado no puede listar tags', function () {
    $this->getJson('/api/tags')->assertStatus(401);
});

test('un usuario autenticado puede listar sus tags', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tags')
        ->assertOk();
});

test('un usuario solo ve sus propios tags en el index', function () {
    $user = User::factory()->create();
    $otro = User::factory()->create();

    Tag::factory()->count(3)->for($user)->create();
    Tag::factory()->for($otro)->create(['name' => 'tag-ajeno']);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/tags')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonMissing(['name' => 'tag-ajeno']);
});

/*
|--------------------------------------------------------------------------
| SHOW
|--------------------------------------------------------------------------
*/

test('un usuario puede ver sus propios tags', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/tags/{$tag->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $tag->id);
});

test('un usuario no puede ver tags de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $tag = Tag::factory()->for($victima)->create();

    $this->actingAs($atacante, 'sanctum')
        ->getJson("/api/tags/{$tag->id}")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| STORE
|--------------------------------------------------------------------------
*/

test('un usuario autenticado puede crear un tag', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tags', ['name' => 'urgente', 'color' => '#ff0000'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'urgente')
        ->assertJsonPath('data.color', '#ff0000');

    $this->assertDatabaseHas('tags', [
        'name' => 'urgente',
        'color' => '#ff0000',
        'user_id' => $user->id,
    ]);
});

test('crear tag valida campos requeridos', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tags', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'color']);
});

test('crear tag rechaza color invalido', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tags', ['name' => 'x', 'color' => 'rojo'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['color']);
});

test('el mismo usuario no puede repetir el nombre de un tag', function () {
    $user = User::factory()->create();
    Tag::factory()->for($user)->create(['name' => 'urgente']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tags', ['name' => 'urgente', 'color' => '#000000'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('dos usuarios distintos pueden tener tags con el mismo nombre', function () {
    $otro = User::factory()->create();
    Tag::factory()->for($otro)->create(['name' => 'urgente']);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/tags', ['name' => 'urgente', 'color' => '#abcdef'])
        ->assertCreated();
});

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

test('un usuario puede actualizar sus propios tags', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create(['name' => 'viejo']);

    $this->actingAs($user, 'sanctum')
        ->putJson("/api/tags/{$tag->id}", ['name' => 'nuevo'])
        ->assertOk()
        ->assertJsonPath('data.name', 'nuevo');
});

test('un usuario no puede actualizar tags de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $tag = Tag::factory()->for($victima)->create(['name' => 'privado']);

    $this->actingAs($atacante, 'sanctum')
        ->putJson("/api/tags/{$tag->id}", ['name' => 'hackeado'])
        ->assertForbidden();

    $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'privado']);
});

/*
|--------------------------------------------------------------------------
| DESTROY
|--------------------------------------------------------------------------
*/

test('un usuario puede borrar sus propios tags', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->for($user)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/tags/{$tag->id}")
        ->assertNoContent();

    $this->assertModelMissing($tag);
});

test('un usuario no puede borrar tags de otro usuario', function () {
    $victima = User::factory()->create();
    $atacante = User::factory()->create();
    $tag = Tag::factory()->for($victima)->create();

    $this->actingAs($atacante, 'sanctum')
        ->deleteJson("/api/tags/{$tag->id}")
        ->assertForbidden();

    $this->assertModelExists($tag);
});

/*
|--------------------------------------------------------------------------
| GUEST 401
|--------------------------------------------------------------------------
*/

test('un invitado no puede usar rutas protegidas de tags', function (string $method, string $path) {
    $this->json($method, $path)->assertStatus(401);
})->with([
    'store'   => ['post',   '/api/tags'],
    'update'  => ['put',    '/api/tags/1'],
    'destroy' => ['delete', '/api/tags/1'],
]);