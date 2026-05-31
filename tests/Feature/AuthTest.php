<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('login');
});

/*
|--------------------------------------------------------------------------
| REGISTER
|--------------------------------------------------------------------------
*/

test('un invitado puede registrarse', function () {
    $payload = [
        'name' => 'Juan Perez',
        'email' => 'juan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->postJson('/api/register', $payload)
        ->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('users', [
        'email' => 'juan@example.com',
        'name' => 'Juan Perez',
    ]);

    $user = User::where('email', 'juan@example.com')->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});

test('register exige campos obligatorios', function () {
    $this->postJson('/api/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('register rechaza email duplicado', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => 'Otro',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('register exige confirmacion de password', function () {
    $this->postJson('/api/register', [
        'name' => 'Juan',
        'email' => 'juan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'diferente',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('register exige password de al menos 8 caracteres', function () {
    $this->postJson('/api/register', [
        'name' => 'Juan',
        'email' => 'juan@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

test('un usuario registrado puede hacer login', function () {
    $user = User::factory()->create(['password' => Hash::make('password123')]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password123',
    ])
        ->assertOk()
        ->assertJsonStructure(['user', 'token']);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('login con credenciales invalidas devuelve 401', function () {
    $user = User::factory()->create(['password' => Hash::make('password123')]);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])
        ->assertStatus(401)
        ->assertJson(['message' => 'Invalid credentials']);
});

test('login con email inexistente devuelve 401', function () {
    $this->postJson('/api/login', [
        'email' => 'noexiste@example.com',
        'password' => 'whatever',
    ])
        ->assertStatus(401);
});

test('login valida campos obligatorios', function () {
    $this->postJson('/api/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

test('login esta limitado a 5 intentos por minuto', function () {
    $payload = ['email' => 'noexiste@example.com', 'password' => 'x'];

    foreach (range(1, 5) as $intento) {
        $this->postJson('/api/login', $payload)->assertStatus(401);
    }

    $this->postJson('/api/login', $payload)->assertStatus(429);
});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

test('un usuario autenticado puede hacer logout y su token queda revocado', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJson(['message' => 'Logged out successfully']);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('logout solo borra el token actual y no los demas del usuario', function () {
    $user = User::factory()->create();
    $tokenActual = $user->createToken('actual')->plainTextToken;
    $user->createToken('otro-dispositivo');

    $this->withHeader('Authorization', "Bearer {$tokenActual}")
        ->postJson('/api/logout')
        ->assertOk();

    $this->assertDatabaseCount('personal_access_tokens', 1);
    $this->assertDatabaseHas('personal_access_tokens', ['name' => 'otro-dispositivo']);
});

test('un invitado no puede hacer logout', function () {
    $this->postJson('/api/logout')->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| /api/user
|--------------------------------------------------------------------------
*/

test('un usuario autenticado puede ver su propio perfil', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email);
});
