# Todo App API

REST API para una aplicación de tareas, construida como proyecto de portafolio para demostrar prácticas profesionales con Laravel.

Incluye autenticación con tokens (Sanctum), autorización por dueño (Policies + scoped queries), validación con Form Requests, formateo de respuesta con API Resources, factories y una suite de tests Pest 4 que cubre los caminos felices, la validación y los casos IDOR (acceso a recursos ajenos).

---

## Stack

- **PHP** 8.3
- **Laravel** 13
- **Laravel Sanctum** 4 (autenticación por token Bearer)
- **PostgreSQL** (producción / desarrollo) · **SQLite en memoria** (tests)
- **Pest** 4 + **pest-plugin-laravel** 4 (tests)

---

## Modelo de dominio

```
User ─┬── hasMany ──► Category
      ├── hasMany ──► Tag
      └── hasMany ──► Task ──► hasMany ──► Subtask
                       └── belongsToMany ──► Tag  (tabla pivot `tag_task`)
```

- Una **Categoría** y un **Tag** pertenecen a un usuario. Los nombres son únicos por usuario (dos usuarios distintos pueden tener un tag "urgente" sin colisión).
- Una **Tarea** pertenece a un usuario y opcionalmente a una categoría suya. Puede llevar muchas etiquetas suyas.
- Una **Subtarea** pertenece a una tarea (su dueño se infiere por la tarea).

---

## Setup

```bash
git clone <repo>
cd todo_app
composer install
cp .env.example .env
php artisan key:generate

# configurar credenciales de PostgreSQL en .env (DB_*)
php artisan migrate
php artisan db:seed     # opcional: 3 usuarios de prueba con datos

php artisan serve
```

Usuarios sembrados (`database/seeders/DatabaseSeeder.php`): `test@example.com`, `ana@example.com`, `luis@example.com`. La contraseña de todos es `password`.

---

## Autenticación

Token Bearer emitido por Sanctum. Flujo:

1. `POST /api/register` o `POST /api/login` → devuelve `{user, token}`.
2. Enviar el token en cada request: `Authorization: Bearer <token>`.
3. `POST /api/logout` para revocar el token actual.

El endpoint de login tiene **rate limit** de 5 intentos por minuto.

---

## Endpoints

> Todas las rutas, excepto `register`, `login` y `GET /up` (health check), exigen `Authorization: Bearer <token>`.

| Método | Ruta | Acción |
|---|---|---|
| `POST` | `/api/register` | Registrar usuario y obtener token |
| `POST` | `/api/login` | Login y obtener token (throttle 5/min) |
| `POST` | `/api/logout` | Revocar el token actual |
| `GET`  | `/api/user` | Devolver el usuario autenticado |
| `GET`  | `/api/categories` | Listar categorías propias (paginado) |
| `POST` | `/api/categories` | Crear categoría |
| `GET`  | `/api/categories/{id}` | Ver categoría |
| `PUT`  | `/api/categories/{id}` | Actualizar categoría |
| `DELETE` | `/api/categories/{id}` | Borrar categoría |
| `GET`  | `/api/tags` | Listar tags propios (paginado) |
| `POST` | `/api/tags` | Crear tag |
| `GET`  | `/api/tags/{id}` | Ver tag |
| `PUT`  | `/api/tags/{id}` | Actualizar tag |
| `DELETE` | `/api/tags/{id}` | Borrar tag |
| `GET`  | `/api/tasks` | Listar tareas propias (con `category`, `tags`, `subtasks`) |
| `POST` | `/api/tasks` | Crear tarea |
| `GET`  | `/api/tasks/{id}` | Ver tarea |
| `PUT`  | `/api/tasks/{id}` | Actualizar tarea |
| `DELETE` | `/api/tasks/{id}` | Borrar tarea |
| `GET`  | `/api/tasks/{task}/subtasks` | Listar subtareas de una tarea |
| `POST` | `/api/tasks/{task}/subtasks` | Crear subtarea en una tarea |
| `GET`  | `/api/subtasks/{id}` | Ver subtarea |
| `PUT`  | `/api/subtasks/{id}` | Actualizar subtarea |
| `DELETE` | `/api/subtasks/{id}` | Borrar subtarea |

Especificación detallada (request/response, validaciones, códigos HTTP): ver [`docs/openapi.yaml`](docs/openapi.yaml).

### Ejemplos con `curl`

```bash
# Registro
curl -X POST http://localhost:8000/api/register \
  -H 'Accept: application/json' \
  -d 'name=Juan&email=juan@example.com&password=password123&password_confirmation=password123'

# Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H 'Accept: application/json' \
  -d 'email=juan@example.com&password=password123' | jq -r .token)

# Crear tarea
curl -X POST http://localhost:8000/api/tasks \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"title":"Comprar pan","priority":"high"}'
```

---

## Seguridad y autorización

- **IDOR cerrado**: cada acción (`view`, `update`, `delete`) pasa por una Policy que compara `user_id` del recurso con el usuario autenticado. Los listados (`index`) usan `$request->user()->{relacion}()->paginate(...)` para que la query nunca devuelva recursos ajenos.
- **Cross-relation**: al crear/actualizar tareas, `category_id` y `tag_ids` se validan con `Rule::exists(...)->where('user_id', $this->user()->id)` — no podés referenciar categorías o tags de otro usuario.
- **Unicidad por usuario**: nombres de Category y Tag son únicos por usuario (validado con `Rule::unique` y, en tags, con índice compuesto en la BD).
- **Passwords** hashed con `bcrypt` (cast `hashed` en `User::$casts`).
- **Login throttle**: 5 intentos por minuto vía middleware `throttle:5,1`.

---

## Tests

Configuración en `phpunit.xml`: SQLite en memoria + `BCRYPT_ROUNDS=4` para que el bcrypt no domine el runtime.

```bash
php artisan test
```

Suite actual: **90 tests, 219 assertions** cubriendo:

| Archivo | Cobertura |
|---|---|
| `tests/Feature/AuthTest.php` | Register, login (credenciales, validación, throttle), logout (revoca token actual, conserva otros), `/api/user`. |
| `tests/Feature/CategoryTest.php` | CRUD completo + IDOR (view/update/delete ajeno → 403) + validación (required, regex de color) + unique scoped + guest 401. |
| `tests/Feature/TagTest.php` | Mismo patrón que categorías. |
| `tests/Feature/TaskTest.php` | CRUD + IDOR + validación + cross-relation (no se puede asignar `category_id` o `tag_ids` de otro usuario) + sincronización de tags en update. |
| `tests/Feature/SubtaskTest.php` | CRUD anidado (ownership por tarea), IDOR en nested store, en show/update/destroy shallow. |

---

## Convenciones del proyecto

- **Form Requests** en `app/Http/Requests/` — toda la validación está fuera de los controladores; `authorize()` delega en la policy correspondiente.
- **API Resources** en `app/Http/Resources/` — fechas en ISO 8601, relaciones cargadas con `whenLoaded(...)` para no exponer datos no pedidos.
- **Policies** en `app/Policies/` — autodescubiertas por Laravel (convención `App\Models\Foo` → `App\Policies\FooPolicy`).
- **Factories** en `database/factories/` — todas las entidades, con states (`completed()`, `highPriority()` en `TaskFactory`).
- **Rutas anidadas** con `Route::apiResource(...)->shallow()` para subtasks: `POST /tasks/{task}/subtasks` para crear, pero `PUT /subtasks/{id}` para actualizar.

---

## Estructura

```
app/
├── Http/
│   ├── Controllers/Api/  (Auth, Category, Tag, Task, Subtask)
│   ├── Requests/         (Store/Update por recurso)
│   └── Resources/        (Category, Tag, Task, Subtask)
├── Models/               (User, Category, Tag, Task, Subtask)
└── Policies/             (Category, Tag, Task, Subtask)
database/
├── factories/
├── migrations/
└── seeders/
tests/Feature/            (Auth, Category, Tag, Task, Subtask)
docs/openapi.yaml         (especificación OpenAPI 3.1)
routes/api.php
```