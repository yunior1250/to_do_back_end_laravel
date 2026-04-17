<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SubtaskController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Ruta protegida - devuelve el usuario autenticado
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API Routes - Todo App
|--------------------------------------------------------------------------
| apiResource genera automáticamente 5 rutas:
|   GET    /tasks           → index   (listar todos)
|   POST   /tasks           → store   (crear)
|   GET    /tasks/{task}    → show    (ver uno)
|   PUT    /tasks/{task}    → update  (editar)
|   DELETE /tasks/{task}    → destroy (borrar)
*/

Route::apiResource('tasks', TaskController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('tags', TagController::class);

// Subtasks anidadas bajo tasks: /api/tasks/{task}/subtasks
Route::apiResource('tasks.subtasks', SubtaskController::class)
    ->shallow(); // shallow: el show/update/destroy usa solo {subtask}
